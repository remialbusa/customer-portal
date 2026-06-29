// Tiny diagnostic: open the ticket show page, scroll to the TSR form,
// and dump its key elements.
import { chromium } from 'playwright';

const PORT = 8765;
const BASE = `http://127.0.0.1:${PORT}`;
const EMAIL = 'remial.busa@mcbtsi.com';
const PASSWORD = 'Password!123';
const TICKET = '2750538828';

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  page.on('pageerror', e => console.log('pageerror:', e.message));
  page.on('console',  m => console.log('console:', m.type(), m.text()));
  page.on('response', r => { if (r.status() >= 400) console.log('http', r.status(), r.url()); });

  await page.goto(`${BASE}/login`);
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASSWORD);
  await Promise.all([
    page.waitForURL(u => !u.toString().endsWith('/login'), { timeout: 15000 }),
    page.click('button[type="submit"]'),
  ]);
  console.log('logged in, url=', page.url());

  const resp = await page.goto(`${BASE}/tsp/tickets/${TICKET}`,
    { waitUntil: 'domcontentloaded' });
  console.log('show page status =', resp?.status());

  try {
    await page.waitForSelector('canvas', { timeout: 10000 });
    console.log('Found canvas (signature pad) — form is rendered.');
  } catch (e) {
    console.log('No canvas found within 10s.');
  }

  const canvasCount = await page.locator('canvas').count();
  const inputCount  = await page.locator('input[type="text"], input[type="email"]').count();
  const formCount   = await page.locator('form').count();
  const livewireTsr = await page.locator('[wire\\:id]').count();
  console.log({ canvasCount, inputCount, formCount, livewireTsr });

  const tsrLegend = await page.locator('text=Customer in charge').count();
  const submitBtn = await page.locator('button:has-text("Submit Report")').count();
  console.log({ tsrLegend, submitBtn });

  await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
