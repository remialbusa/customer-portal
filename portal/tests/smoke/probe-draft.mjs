// Diagnostic: open form, fill marker, check console logs and localStorage.
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
page.on('request',  r => { if (r.url().includes('livewire') || r.url().includes('update')) console.log('[req]', r.method(), r.url().substring(0, 200)); });
page.on('response', r => { if (r.url().includes('livewire') || r.url().includes('update')) console.log('[res]', r.status(), r.url().substring(0, 200)); });

await page.goto(`${BASE}/login`);
await page.fill('input[name="email"]', EMAIL);
await page.fill('input[name="password"]', PASSWORD);
await Promise.all([
  page.waitForURL(u => !u.toString().endsWith('/login'), { timeout: 15000 }),
  page.click('button[type="submit"]'),
]);
console.log('logged in, url=', page.url());

await page.goto(`${BASE}/tsp/tickets/${TICKET}`, { waitUntil: 'domcontentloaded' });
await page.waitForSelector('canvas.signature-pad__canvas', { timeout: 20000 });
await wait(500);  // give Alpine time to init

// Check what window globals exist
const globals = await page.evaluate(() => ({
  hasTsrForm: typeof window.tsrForm,
  hasSignaturePad: typeof window.signaturePad,
  hasAlpine: typeof window.Alpine,
  hasLivewire: typeof window.Livewire,
  tsrLocalId: window.__tsrLocalId,
  tsrTicketNumber: window.__tsrTicketNumber,
  alpineRoot: !! document.querySelector('[x-data*="tsrForm"]'),
  formCount: document.querySelectorAll('form').length,
  canvasCount: document.querySelectorAll('canvas').length,
  textInputs: Array.from(document.querySelectorAll('form[wire\\:submit] input[type="text"]')).map(i => i.name || i.getAttribute('wire:model')),
}));
console.log('globals:', JSON.stringify(globals, null, 2));

// Find and fill the first text input
const firstTextInput = page.locator('form input[type="text"]').first();
const inputInfo = await firstTextInput.evaluate(el => ({ name: el.name, model: el.getAttribute('wire:model') }));
console.log('first text input:', inputInfo);

const marker = `DRAFT-MARKER-${Date.now()}`;
await firstTextInput.fill(marker);
console.log('typed marker:', marker);
await wait(2000);  // longer wait for autosave

// Check localStorage
const keys = await page.evaluate(() => Object.keys(localStorage));
console.log('localStorage keys:', keys);

const tsrKeys = await page.evaluate(() => Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.')));
console.log('tsr.draft.* keys:', tsrKeys);
if (tsrKeys.length > 0) {
  const draft = await page.evaluate((k) => JSON.parse(localStorage.getItem(k)), tsrKeys[0]);
  console.log('draft content:', JSON.stringify(draft, null, 2).substring(0, 1500));
}

await browser.close();
