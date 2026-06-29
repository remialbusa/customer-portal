// End-to-end smoke test: logs in as a TSP, opens the TSR form for a ticket,
// signs the 3 signature pads, and submits. Then checks the DB row.
//
// The TSR submission triggers the SAME code path as the drainer
// (App\Actions\SyncPendingTsrReports::syncOne).
import { setTimeout as wait } from 'node:timers/promises';
import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { chromium } from 'playwright';

const PORT = 8765;
const BASE = `http://127.0.0.1:${PORT}`;
const EMAIL = 'remial.busa@mcbtsi.com';
const PASSWORD = 'Password!123';

function log(...args) { console.log('[e2e]', ...args); }
function fail(msg) { console.error('[e2e][FAIL]', msg); process.exit(1); }

(async () => {
  // 1) Read the current ticket id from the SQLite database via PHP
  const db = path.resolve('database/database.sqlite');
  if (!fs.existsSync(db)) fail('database.sqlite not found');
  const ticketRow = execSync(`php tests/smoke/get_ticket.php`, { encoding: 'utf8' }).trim();
  if (!ticketRow) fail('no service_reports row id=16 found');
  const ticketId = ticketRow;
  log(`target ticket monday id = ${ticketId}`);

  // 2) Open browser (bundled Chromium)
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  page.on('pageerror', e => log('pageerror:', e.message));

  // 3) Log in
  log('opening login page');
  await page.goto(`${BASE}/login`);
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASSWORD);
  await Promise.all([
    page.waitForURL(u => !u.toString().endsWith('/login'), { timeout: 15000 }),
    page.click('button[type="submit"]'),
  ]);
  log('logged in, current url =', page.url());

  // 4) Open the TSP ticket show page
  log('opening ticket show page');
  const showUrl = `${BASE}/tsp/tickets/${ticketId}`;
  const resp = await page.goto(showUrl, { waitUntil: 'domcontentloaded' });
  log('show page status =', resp?.status());
  if (resp?.status() !== 200) fail(`show page returned ${resp?.status()}`);

  // 5) Wait for the TSR form to hydrate
  log('waiting for the TSR form');
  await page.waitForSelector('form[wire\\:submit\\.prevent]', { timeout: 20000 });
  await page.waitForSelector('canvas.signature-pad__canvas', { timeout: 20000 });
  log('TSR form ready');

  // 6) Fill any textareas with a smoke note
  const textareas = page.locator('form[wire\\:submit\\.prevent] textarea');
  const taCount = await textareas.count();
  for (let i = 0; i < taCount; i++) {
    await textareas.nth(i).fill(`E2E smoke note #${i + 1} at ${new Date().toISOString()}`);
  }

  // 7) Draw on each signature canvas
  const pads = page.locator('canvas.signature-pad__canvas');
  const padCount = await pads.count();
  log(`found ${padCount} signature canvases`);
  for (let i = 0; i < padCount; i++) {
    const box = await pads.nth(i).boundingBox();
    if (!box) { log(`pad ${i} has no bbox, skipping`); continue; }
    const x0 = box.x + 20, y0 = box.y + 20;
    await page.mouse.move(x0, y0);
    await page.mouse.down();
    for (let k = 0; k <= 12; k++) {
      await page.mouse.move(
        x0 + (box.width - 40) * (k / 12),
        y0 + (box.height - 40) * Math.sin(k / 2),
        { steps: 2 }
      );
    }
    await page.mouse.up();
  }

  // 8) Click the "Save TSR" submit button
  log('submitting form');
  const saveBtn = page.locator('form[wire\\:submit\\.prevent] button[type="submit"]');
  await saveBtn.first().click();
  await page.waitForLoadState('networkidle').catch(() => {});
  await wait(2000);

  // 9) Wait for the sync to run, then check DB
  log('waiting for sync to run');
  await wait(4000);
  const out = execSync(`php tests/smoke/dump_tsr.php`, { encoding: 'utf8' }).trim();
  log('DB row after submit:');
  console.log(out);

  // 10) Best-effort: reupload command (will fail if Monday is still down)
  try {
    const r = execSync('php artisan monday:reupload-signatures --tsr-id=16', { encoding: 'utf8' });
    log('reupload result:');
    console.log(r);
  } catch (e) {
    log('reupload command failed (likely Monday outage):',
      (e.stdout || e.message).toString().split('\n').slice(-5).join('\n'));
  }

  await browser.close();
  log('done');
})().catch(e => fail(e.stack || e.message));
