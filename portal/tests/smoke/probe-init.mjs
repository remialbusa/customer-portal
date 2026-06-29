// Diagnostic: just load the page and inspect Alpine state.
import { setTimeout as wait } from 'node:timers/promises';
import { chromium } from 'playwright';

const BASE = 'http://127.0.0.1:8765';
const EMAIL = 'remial.busa@mcbtsi.com';
const PASSWORD = 'Password!123';
const TICKET = '2750538828';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
page.on('pageerror', e => console.log('[pageerror]', e.message));
page.on('console',  m => console.log('[' + m.type() + ']', m.text()));
page.on('requestfailed', r => console.log('[reqfail]', r.url(), r.failure()?.errorText));
page.on('response', r => { if (r.status() >= 400) console.log('[resp]', r.status(), r.url()); });

await page.goto(`${BASE}/login`);
await page.fill('input[name="email"]', EMAIL);
await page.fill('input[name="password"]', PASSWORD);
await Promise.all([
  page.waitForURL(u => !u.toString().endsWith('/login'), { timeout: 15000 }),
  page.click('button[type="submit"]'),
]);

await page.goto(`${BASE}/tsp/tickets/${TICKET}`, { waitUntil: 'domcontentloaded' });
await page.waitForSelector('canvas.signature-pad__canvas', { timeout: 20000 });

// Inject an Alpine init spy
await page.evaluate(() => {
  const root = document.querySelector('.tsr-form');
  console.log('TSR root:', !!root, root?.outerHTML?.slice(0, 200));
  if (root && root._x_dataStack) {
    const data = root._x_dataStack[0];
    console.log('Alpine data keys:', Object.keys(data).filter(k => k.startsWith('_') || k === 'hasDraft' || k === 'online').join(','));
    console.log('_initialized:', data._initialized);
    console.log('_draftKey:', data._draftKey);
    console.log('_draftRestored:', data._draftRestored);
    console.log('hasDraft:', data.hasDraft);
  } else {
    console.log('no _x_dataStack on .tsr-form');
    // Check if Alpine is on the element
    console.log('attrs:', root ? Array.from(root.attributes).map(a => a.name).join(',') : 'no root');
  }
});

await wait(2000);

// Check Alpine state
const state = await page.evaluate(() => {
  const root = document.querySelector('.tsr-form');
  if (! root || ! root._x_dataStack) return { error: 'no Alpine data' };
  const data = root._x_dataStack[0];
  return {
    initialized: data._initialized,
    draftKey: data._draftKey,
    draftRestored: data._draftRestored,
    hasDraft: data.hasDraft,
    methods: Object.keys(data).filter(k => typeof data[k] === 'function' && k.startsWith('_')).join(','),
  };
});
console.log('STATE:', state);

await browser.close();
