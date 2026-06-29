// Just inspect what's in localStorage after seed.
import { setTimeout as wait } from 'node:timers/promises';
import { chromium } from 'playwright';

const BASE = 'http://127.0.0.1:8765';
const EMAIL = 'remial.busa@mcbtsi.com';
const PASSWORD = 'Password!123';
const TICKET = '2750538828';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();

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

// Seed with explicit fields
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
await page.evaluate((args) => {
  localStorage.setItem(args.key, JSON.stringify(args.value));
}, { key: draftKey, value: seed });

// Verify what we just wrote
const stored = await page.evaluate((k) => localStorage.getItem(k), draftKey);
console.log('STORED:', stored);

await browser.close();
