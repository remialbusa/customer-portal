// Deep diagnostic: open form, type, then check Alpine state and localStorage.
import { setTimeout as wait } from 'node:timers/promises';
import { chromium } from 'playwright';

const BASE = 'http://127.0.0.1:8765';
const EMAIL = 'remial.busa@mcbtsi.com';
const PASSWORD = 'Password!123';
const TICKET = '2750538828';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();
page.on('pageerror', e => console.log('[pageerror]', e.message));
page.on('console',  m => console.log('[console.' + m.type() + ']', m.text()));

await page.goto(`${BASE}/login`);
await page.fill('input[name="email"]', EMAIL);
await page.fill('input[name="password"]', PASSWORD);
await Promise.all([
  page.waitForURL(u => !u.toString().endsWith('/login'), { timeout: 15000 }),
  page.click('button[type="submit"]'),
]);

await page.goto(`${BASE}/tsp/tickets/${TICKET}`, { waitUntil: 'domcontentloaded' });
await page.waitForSelector('canvas.signature-pad__canvas', { timeout: 20000 });
await wait(1500);

// Inspect the TSR form's Alpine component
const tsrInfo = await page.evaluate(() => {
  const root = document.querySelector('form[wire\\:submit] .tsr-form')
            || document.querySelector('form[wire\\:submit]')
            || document.querySelector('[x-data*="tsrForm"]');
  if (! root) return { error: 'no tsr root found' };

  // Find Alpine data
  const data = window.Alpine ? window.Alpine.$data(root) : null;
  if (! data) return { error: 'no Alpine data on root', root: root.outerHTML.substring(0, 200) };

  return {
    hasInit: typeof data.init,
    hasSchedule: typeof data._scheduleDraftSave,
    hasSaveNow: typeof data._saveDraftNow,
    hasDraftAvailable: data._draftAvailable,
    hasDraft: data.hasDraft,
    draftKey: data._draftKey,
    draftRestored: data._draftRestored,
    draftSavingTimer: data._draftSaveTimer,
    ticketNumber: data.ticketNumber,
    localId: data.localId,
    fields: Object.keys(data).filter(k => ! k.startsWith('_')),
  };
});
console.log('tsrInfo:', JSON.stringify(tsrInfo, null, 2));

// Try the form's wire:submit form
const tsrForm = page.locator('form[wire\\:submit\\.prevent]').first();
await tsrForm.waitFor({ timeout: 5000 });
const formInfo = await tsrForm.evaluate(el => ({
  classes: el.className,
  inputs: Array.from(el.querySelectorAll('input,textarea,select')).map(i => ({
    type: i.type, name: i.name, model: i.getAttribute('wire:model'), value: i.value
  })),
}));
console.log('tsrFormInfo:', JSON.stringify(formInfo, null, 2));

// Now type into a TSR input
const tspName = page.locator('form[wire\\:submit\\.prevent] input[wire\\:model="tspName"]').first();
const exists = await tspName.count();
console.log('tspName input exists:', exists);
if (exists) {
  await tspName.fill('TEST-TSP-NAME');
  await wait(1500);
  const keys = await page.evaluate(() => Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.')));
  console.log('tsr.draft.* keys after typing tspName:', keys);
  if (keys.length > 0) {
    const draft = await page.evaluate((k) => localStorage.getItem(k), keys[0]);
    console.log('draft:', draft);
  }
}

await browser.close();
