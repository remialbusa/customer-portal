// Diagnostic: seed localStorage, then load the page, then check hydrate.
import { setTimeout as wait } from 'node:timers/promises';
import { chromium } from 'playwright';

const BASE = 'http://127.0.0.1:8765';
const EMAIL = 'remial.busa@mcbtsi.com';
const PASSWORD = 'Password!123';
const TICKET = '2750538828';

const browser = await chromium.launch({ headless: true });
const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
const page = await ctx.newPage();
page.on('pageerror', e => console.log('[pageerror]', e.message));
page.on('console',  m => console.log('[' + m.type() + ']', m.text()));

// First go to the page once to get the localId
await page.goto(`${BASE}/login`);
await page.fill('input[name="email"]', EMAIL);
await page.fill('input[name="password"]', PASSWORD);
await Promise.all([
  page.waitForURL(u => !u.toString().endsWith('/login'), { timeout: 15000 }),
  page.click('button[type="submit"]'),
]);
await page.goto(`${BASE}/tsp/tickets/${TICKET}`, { waitUntil: 'domcontentloaded' });
await page.waitForSelector('canvas.signature-pad__canvas', { timeout: 20000 });
const localId = await page.evaluate(() => window.__tsrLocalId);
const ticket = await page.evaluate(() => window.__tsrTicketNumber);
console.log('localId:', localId, 'ticket:', ticket);

const draftKey = 'tsr.draft.' + ticket;
const seed = {
  v: 1,
  savedAt: Date.now(),
  localId: localId,
  ticket: ticket,
  fields: {
    tspSignatureName: 'SEED-MARKER-XYZ',
    customerName: 'Seed Customer',
    customerEmail: 'seed@test.local',
    biomedName: 'Seed Biomed',
    biomedEmail: 'biomed@test.local',
    tspSignatureDataUrl: 'data:image/png;base64,SEED_TSP',
  },
  signatures: {
    tspSignatureDataUrl: 'data:image/png;base64,SEED_TSP',
    customerSignatureDataUrl: '',
    biomedSignatureDataUrl: '',
  },
};
await page.evaluate((args) => localStorage.setItem(args.key, JSON.stringify(args.value)), { key: draftKey, value: seed });
console.log('seeded draft key:', draftKey);

// Reload — this should trigger hydrate
await page.reload({ waitUntil: 'domcontentloaded' });
await page.waitForSelector('canvas.signature-pad__canvas', { timeout: 20000 });
await wait(2000);

const result = await page.evaluate(() => {
  const root = document.querySelector('.tsr-form');
  const data = root?._x_dataStack?.[0];
  return {
    draftRestored: data?._draftRestored,
    tspSigName: document.querySelector('input[wire\\:model="tspSignatureName"]')?.value,
    customerName: document.querySelector('input[wire\\:model="customerName"]')?.value,
    customerEmail: document.querySelector('input[wire\\:model="customerEmail"]')?.value,
    biomedName: document.querySelector('input[wire\\:model="biomedName"]')?.value,
    biomedEmail: document.querySelector('input[wire\\:model="biomedEmail"]')?.value,
    tspSigHidden: document.querySelector('input[name="tspSignatureDataUrl"]')?.value?.slice(0, 40),
  };
});
console.log('RESULT:', result);

await browser.close();
