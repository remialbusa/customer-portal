// Diagnostic: save draft, reload, see what happens with the input value.
import { setTimeout as wait } from 'node:timers/promises';
import { chromium } from 'playwright';

const BASE = 'http://127.0.0.1:8765';
const EMAIL = 'remial.busa@mcbtsi.com';
const PASSWORD = 'Password!123';
const TICKET = '2750538828';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
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

// Clear any prior draft
await page.evaluate(() => Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.')).forEach(k => localStorage.removeItem(k)));

// Install a hydrate spy
await page.evaluate(() => {
  window.__hydrateCalled = 0;
  window.addEventListener('tsr.draft-restored', () => { window.__hydrateCalled++; });
});

// Type into tspSignatureName
const tspName = page.locator('input[wire\\:model="tspSignatureName"]').first();
await tspName.fill('TEST-RESTORE-MARKER');
await wait(1500);

const beforeReload = await page.evaluate(() => ({
  lsKeys: Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.')),
  inputValue: document.querySelector('input[wire\\:model="tspSignatureName"]')?.value,
}));
console.log('BEFORE RELOAD:', beforeReload);

// Reload
await page.reload({ waitUntil: 'domcontentloaded' });
await page.waitForSelector('canvas.signature-pad__canvas', { timeout: 20000 });

// Check input value at various points
for (const ms of [50, 200, 500, 1000, 2000, 5000]) {
  await wait(ms);
  const v = await page.evaluate(() => ({
    hydrate: window.__hydrateCalled,
    inputValue: document.querySelector('input[wire\\:model="tspSignatureName"]')?.value,
    lsKey: Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.'))[0],
  }));
  console.log(`@${ms}ms:`, v);
}

await browser.close();
