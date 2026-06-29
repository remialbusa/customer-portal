// Deep diagnostic: log livewire:updated and verify init ran.
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

// Install a spy for livewire:updated
await page.evaluate(() => {
  window.__luUpdates = 0;
  window.addEventListener('livewire:updated', () => { window.__luUpdates++; });
  window.__tsrSigCommitted = 0;
  window.addEventListener('tsr.signature-committed', () => { window.__tsrSigCommitted++; });
});

// Type into tspSignatureName
const tspName = page.locator('input[wire\\:model="tspSignatureName"]').first();
await tspName.fill('TEST-DRAFT-MARKER-XYZ');
await wait(2000);

const stats = await page.evaluate(() => ({
  luUpdates: window.__luUpdates,
  sigCommitted: window.__tsrSigCommitted,
  tsrLocalId: window.__tsrLocalId,
  tsrTicketNumber: window.__tsrTicketNumber,
  lsKeys: Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.')),
  // Also check the Alpine state
  rootData: (() => {
    const root = document.querySelector('[x-data*="tsrForm"]');
    if (! root || ! window.Alpine) return null;
    const d = window.Alpine.$data(root);
    return {
      _draftKey: d._draftKey,
      _draftAvailable: d._draftAvailable,
      hasDraft: d.hasDraft,
      _draftSaveTimer: d._draftSaveTimer,
      _initialized: d._initialized,
    };
  })(),
}));
console.log('STATS:', JSON.stringify(stats, null, 2));

// Now manually call _scheduleDraftSave via Alpine
const result = await page.evaluate(() => {
  const root = document.querySelector('[x-data*="tsrForm"]');
  if (! root || ! window.Alpine) return 'no root';
  const d = window.Alpine.$data(root);
  if (typeof d._scheduleDraftSave === 'function') {
    d._scheduleDraftSave();
    return 'called _scheduleDraftSave';
  }
  return 'no _scheduleDraftSave method';
});
console.log('Manual call:', result);
await wait(2000);

const stats2 = await page.evaluate(() => ({
  lsKeys: Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.')),
  rootHasDraft: window.Alpine.$data(document.querySelector('[x-data*="tsrForm"]')).hasDraft,
}));
console.log('STATS2:', JSON.stringify(stats2, null, 2));

await browser.close();
