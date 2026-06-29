// Diagnostic: scroll canvas into view, draw, check.
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

await page.evaluate(() => {
  window.__sigEvents = [];
  window.addEventListener('tsr.signature-committed', (ev) => {
    window.__sigEvents.push({ name: ev.detail?.name, len: ev.detail?.dataUrl?.length || 0 });
  });
});

// Scroll first canvas into view
const firstPad = page.locator('canvas.signature-pad__canvas').first();
await firstPad.scrollIntoViewIfNeeded();
await wait(500);
const box = await firstPad.boundingBox();
console.log('canvas box:', box);

// Draw
await page.mouse.move(box.x + 20, box.y + 20);
await page.mouse.down();
for (let k = 0; k <= 12; k++) {
  await page.mouse.move(
    box.x + (box.width - 40) * (k / 12),
    box.y + (box.height - 40) * Math.sin(k / 2),
    { steps: 2 }
  );
}
await page.mouse.up();
await wait(2000);

const events = await page.evaluate(() => window.__sigEvents);
console.log('sig events:', events);

const keys = await page.evaluate(() => Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.')));
console.log('ls keys:', keys);
if (keys.length > 0) {
  const draft = await page.evaluate((k) => JSON.parse(localStorage.getItem(k)), keys[0]);
  console.log('draft fields:', Object.keys(draft.fields || {}));
  console.log('draft signatures:', Object.entries(draft.signatures || {}).map(([k, v]) => [k, typeof v, v ? v.length : 0]));
}

await browser.close();
