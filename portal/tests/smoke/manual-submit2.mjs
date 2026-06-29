// Carefully drive Submit Report and capture the server response
import { setTimeout as wait } from 'node:timers/promises';
import { chromium } from 'playwright';

const BASE = 'http://127.0.0.1:8765';
const TICKET = '2750538828';
const log = (...a) => console.log('[submit2]', ...a);

const browser = await chromium.launch({ headless: false, channel: 'msedge' });
const ctx = await browser.newContext();
const page = await ctx.newPage();

// Capture every livewire/update POST so we can see what the server replies
const updates = [];
page.on('response', async r => {
  if (r.url().endsWith('/livewire/update') && r.request().method() === 'POST') {
    let body = '';
    try { body = await r.text(); } catch {}
    updates.push({
      status: r.status(),
      sent_payload: r.request().postData()?.slice(0, 400),
      response_body: body.slice(0, 800),
    });
  }
});
page.on('pageerror', e => console.log('[pageerror]', e.message));
page.on('console',  m => {
  if (['error','warning'].includes(m.type())) console.log('[' + m.type() + ']', m.text().slice(0, 300));
});

// login
await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
await page.fill('input[name="email"]', 'remial.busa@mcbtsi.com');
await page.fill('input[name="password"]', 'Password!123');
await Promise.all([
  page.waitForURL(u => !u.toString().endsWith('/login'), { timeout: 20000 }),
  page.click('button[type="submit"]'),
]);
log('logged in, url =', page.url());

await page.goto(`${BASE}/tsp/tickets/${TICKET}`, { waitUntil: 'domcontentloaded' });
await page.waitForSelector('canvas.signature-pad__canvas', { timeout: 20000 });
await wait(1500);

// wipe drafts
await page.evaluate(() => {
  Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.')).forEach(k => localStorage.removeItem(k));
});

// Helper: fill any wire:model form control regardless of .live/.blur
async function setField(model, value) {
  const sel = `[wire\\:model="${model}"], [wire\\:model\\.live="${model}"], [wire\\:model\\.blur="${model}"]`;
  const el = page.locator(sel).first();
  await el.scrollIntoViewIfNeeded();
  await el.fill(value);
}

log('filling required fields...');
await setField('problemAndConcerns', 'Printer is not powering on.');
await setField('jobDone',             'Replaced the PSU and verified boot.');
await setField('partsReplaced',       'PSU P/N ABC-123');
await setField('recommendation',      'Schedule next PM in 90 days.');
await setField('remarks',             'Customer trained on power cycle.');
await setField('machineSystemSerialNumber', 'SN-9999-XYZ');
await setField('softwareVersionNo',   'FW 4.2.1');
await setField('logInDate',           '2026-06-22T08:00');
await setField('serviceStartDateTime','2026-06-22T08:00');
await setField('serviceEndDateTime',  '2026-06-22T10:30');
await setField('logOutDate',          '2026-06-22T10:30');
await setField('tspSignatureName',    'My Test FSE');
await setField('customerName',        'Jane Customer');
await setField('customerEmail',       'jane@example.com');
await setField('biomedName',          'Bob Biomed');
await setField('biomedEmail',         'bob@example.com');
await setField('tspWorkWithCsv',      '12345,67890');
await wait(1000);

async function drawOn(idx) {
  const pad = page.locator('canvas.signature-pad__canvas').nth(idx);
  await pad.scrollIntoViewIfNeeded();
  const box = await pad.boundingBox();
  await page.mouse.move(box.x + 30, box.y + box.height / 2);
  await page.mouse.down();
  for (let k = 0; k <= 18; k++) {
    await page.mouse.move(
      box.x + 30 + (box.width - 60) * (k / 18),
      box.y + box.height / 2 + Math.sin(k * 0.8) * (box.height / 4),
      { steps: 3 }
    );
  }
  await page.mouse.up();
  await wait(600);
}
log('drawing signatures...');
await drawOn(0);
await drawOn(1);
await drawOn(2);
await wait(1500);

const submitBtn = page.locator('button:has-text("Submit Report")').first();
await submitBtn.scrollIntoViewIfNeeded();
await wait(300);
log('clicking Submit Report...');
const updatesBefore = updates.length;
await submitBtn.click();
// Livewire roundtrips may take a while
await wait(8000);

const newUpdates = updates.slice(updatesBefore);
log('livewire/update calls during submit:', newUpdates.length);
for (const u of newUpdates) {
  log('  status=' + u.status);
  log('  sent:    ' + (u.sent_payload || '').slice(0, 350));
  log('  reply:   ' + u.response_body.slice(0, 700));
}

const errBanner = await page.locator('text=Save failed:').first().textContent().catch(() => null);
const okBanner  = await page.locator('text=TSR saved locally').first().textContent().catch(() => null);
log('error banner:',  errBanner ? errBanner.trim() : '(none)');
log('success banner:', okBanner  ? okBanner.trim()  : '(none)');

// Dump the last 30 log lines
log('--- recent laravel.log ---');
const logFile = `${process.cwd()}\\storage\\logs\\laravel.log`;
try {
  const { execSync } = await import('node:child_process');
  const tail = execSync(`powershell -NoProfile -Command "Get-Content '${logFile}' -Tail 40"`, { encoding: 'utf8' });
  console.log(tail);
} catch (e) {
  log('could not tail log:', e.message);
}

await new Promise(r => page.on('close', r));
await browser.close();
