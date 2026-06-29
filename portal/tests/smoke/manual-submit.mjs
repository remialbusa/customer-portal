// Reproduce the "Submit Report" failure in a real browser.
import { setTimeout as wait } from 'node:timers/promises';
import { chromium } from 'playwright';

const BASE  = 'http://127.0.0.1:8765';
const EMAIL = 'remial.busa@mcbtsi.com';
const PASSWORD = 'Password!123';
const TICKET = '2750538828';
const log = (...a) => console.log('[submit-test]', ...a);

const browser = await chromium.launch({
  headless: false, channel: 'msedge',
  args: ['--no-sandbox', '--disable-dev-shm-usage'],
});
const ctx = await browser.newContext({ viewport: { width: 1400, height: 1000 } });
const page = await ctx.newPage();

const responses = [];
page.on('response', r => {
  if (r.url().includes('/livewire/') || r.url().match(/\/submit|\/store/)) {
    responses.push({ url: r.url(), status: r.status(), method: r.request().method() });
  }
});
page.on('pageerror', e => console.log('[pageerror]', e.message));
page.on('console',  m => {
  if (m.type() === 'error' || m.type() === 'warning' || m.text().toLowerCase().includes('livewire')) {
    console.log('[' + m.type() + ']', m.text());
  }
});

// login
await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
await page.fill('input[name="email"]', EMAIL);
await page.fill('input[name="password"]', PASSWORD);
await Promise.all([
  page.waitForURL(u => !u.toString().endsWith('/login'), { timeout: 20000 }),
  page.click('button[type="submit"]'),
]);
log('logged in, url =', page.url());

// ticket
await page.goto(`${BASE}/tsp/tickets/${TICKET}`, { waitUntil: 'domcontentloaded' });
await page.waitForSelector('canvas.signature-pad__canvas', { timeout: 20000 });
await wait(1500);

// wipe any saved draft so we start clean
await page.evaluate(() => {
  Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.')).forEach(k => localStorage.removeItem(k));
});
log('cleared any prior drafts.');

// fill the minimum required fields
async function fill(model, value) {
  const sel = `[wire\\:model="${model}"], [wire\\:model\\.live="${model}"], [wire\\:model\\.blur="${model}"]`;
  await page.locator(sel).first().fill(value);
}

log('filling required fields...');
// Dump all wire:model attributes on the page so we can see what we have
const wireModels = await page.evaluate(() => {
  return [...document.querySelectorAll('[wire\\:model]')].map(el => ({
    tag: el.tagName, type: el.type || null, model: el.getAttribute('wire:model'),
  }));
});
log('wire:model elements on page:', JSON.stringify(wireModels, null, 2));
await fill('problemAndConcerns', 'Printer is not powering on.');
await fill('jobDone',             'Replaced the PSU and verified boot.');
await fill('partsReplaced',       'PSU P/N ABC-123');
await fill('recommendation',      'Schedule next PM in 90 days.');
await fill('remarks',             'Customer trained on power cycle.');
await fill('machineSystemSerialNumber', 'SN-9999-XYZ');
await fill('softwareVersionNo',   'FW 4.2.1');

await fill('tspSignatureName', 'My Test FSE');
await fill('customerName',     'Jane Customer');
await fill('customerEmail',    'jane@example.com');
await fill('biomedName',       'Bob Biomed');
await fill('biomedEmail',      'bob@example.com');

await wait(800);

// Draw a quick signature on each pad
async function drawOn(idx) {
  const pad = page.locator('canvas.signature-pad__canvas').nth(idx);
  await pad.scrollIntoViewIfNeeded();
  const box = await pad.boundingBox();
  await page.mouse.move(box.x + 30, box.y + box.height / 2);
  await page.mouse.down();
  for (let k = 0; k <= 16; k++) {
    await page.mouse.move(
      box.x + 30 + (box.width - 60) * (k / 16),
      box.y + box.height / 2 + Math.sin(k * 0.8) * (box.height / 4),
      { steps: 3 }
    );
  }
  await page.mouse.up();
  await wait(500);
}
log('drawing signatures...');
await drawOn(0);
await drawOn(1);
await drawOn(2);
await wait(1200);

// scroll the submit button into view
const submitBtn = page.locator('button:has-text("Submit Report")').first();
await submitBtn.scrollIntoViewIfNeeded();
await wait(300);
log('clicking Submit Report...');
const t0 = Date.now();
await submitBtn.click();
await wait(5000);

const errBanner = await page.locator('text=Save failed:').first().textContent().catch(() => null);
const okBanner  = await page.locator('text=TSR saved locally').first().textContent().catch(() => null);
log('error banner:', errBanner ? errBanner.trim() : '(none)');
log('success banner:', okBanner ? okBanner.trim() : '(none)');
log('elapsed:', (Date.now() - t0) + 'ms');
log('livewire responses:', JSON.stringify(responses.filter(r => r.url.includes('livewire')), null, 2));

// leave the page open
await new Promise(r => page.on('close', r));
await browser.close();
