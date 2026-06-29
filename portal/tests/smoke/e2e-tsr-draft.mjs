// End-to-end smoke test: draft autosave.
//   1) Open the TSR form for a ticket
//   2) Fill a few fields + draw a signature
//   3) Wait for the debounced draft save to fire
//   4) Read the draft back from localStorage and assert shape
//   5) Reload the page; assert the fields + canvas come back
//   6) Click "Discard draft"; assert the localStorage entry is gone
//
// This test does NOT submit the form. It only exercises the new
// in-browser autosave path.
import { setTimeout as wait } from 'node:timers/promises';
import { chromium } from 'playwright';

const PORT = 8765;
const BASE = `http://127.0.0.1:${PORT}`;
const EMAIL = 'remial.busa@mcbtsi.com';
const PASSWORD = 'Password!123';
const TICKET = '2750538828'; // Monday item id; /tsp/tickets/{mondayItemId}

function log(...args) { console.log('[draft]', ...args); }
function fail(msg) { console.error('[draft][FAIL]', msg); process.exit(1); }

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  page.on('pageerror', e => log('pageerror:', e.message));

  // 1) Login
  log('logging in');
  await page.goto(`${BASE}/login`);
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASSWORD);
  await Promise.all([
    page.waitForURL(u => !u.toString().endsWith('/login'), { timeout: 15000 }),
    page.click('button[type="submit"]'),
  ]);

  // 2) Open the TSR form for the target ticket
  log('opening ticket show page');
  await page.goto(`${BASE}/tsp/tickets/${TICKET}`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('canvas.signature-pad__canvas', { timeout: 20000 });

  // Locate the TSR form: it's the form that contains a signature canvas.
  const tsrFormLocator = page.locator('form:has(canvas.signature-pad__canvas)').first();
  await tsrFormLocator.waitFor({ timeout: 10000 });

  // 3) Fill a unique marker into the TSP signature name input
  const marker = `DRAFT-MARKER-${Date.now()}`;
  const tspNameInput = tsrFormLocator.locator('input[wire\\:model="tspSignatureName"]').first();
  await tspNameInput.fill(marker);
  log(`typed marker into tspSignatureName: ${marker}`);

  // 4) Draw on the FIRST signature pad only (so we can verify ink comes back)
  const firstPad = page.locator('canvas.signature-pad__canvas').first();
  // The page is tall (TSR form is below the ticket header) so scroll the
  // canvas into view before we start drawing — otherwise the mouse
  // coordinates land off-screen and the pointer events never fire.
  await firstPad.scrollIntoViewIfNeeded();
  await wait(200);
  const box = await firstPad.boundingBox();
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

  // 5) Wait for the debounced save (400ms) + a buffer
  await wait(900);

  // 6) Read the draft from localStorage and assert it exists + has data
  const draftKeys = await page.evaluate(() => Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.')));
  log('localStorage tsr.draft.* keys:', draftKeys);
  if (draftKeys.length === 0) fail('no draft key in localStorage after typing + drawing');

  const draftRaw = await page.evaluate((k) => localStorage.getItem(k), draftKeys[0]);
  const draft = JSON.parse(draftRaw);
  if (! draft.fields)                fail('draft has no fields');
  if (draft.fields.problemAndConcerns !== marker) {
    // The first text input might be tspSignatureName / customerName depending on DOM order.
    // Just assert SOME field captured the marker.
    const matches = Object.values(draft.fields).includes(marker);
    if (! matches) fail(`marker not found in any draft field. fields: ${JSON.stringify(Object.keys(draft.fields))}`);
  }
  if (! draft.signatures) fail('draft has no signatures block');
  const sigCount = Object.values(draft.signatures).filter(v => typeof v === 'string' && v.startsWith('data:image/png')).length;
  log(`draft captured ${sigCount} signature(s) as data URLs`);
  if (sigCount < 1) fail('expected at least 1 signature data URL in draft');

  // 7) Reload the page
  log('reloading the page');
  await page.reload({ waitUntil: 'domcontentloaded' });
  await page.waitForSelector('canvas.signature-pad__canvas', { timeout: 20000 });
  const tsrFormLocator2 = page.locator('form:has(canvas.signature-pad__canvas)').first();
  await tsrFormLocator2.waitFor({ timeout: 10000 });
  // Give the draft hydrate a moment to fire
  await wait(800);

  // 8) Assert the marker is back in tspSignatureName
  const restored = await tsrFormLocator2.locator('input[wire\\:model="tspSignatureName"]').first().inputValue();
  log('tspSignatureName after reload:', restored);
  if (restored !== marker) {
    fail(`marker was NOT restored after reload. expected '${marker}', got '${restored}'`);
  }
  log('OK: text marker restored after reload');

  // 9) Assert the first signature canvas has ink (non-blank pixels)
  const hasInk = await page.evaluate(() => {
    const canvas = document.querySelector('canvas.signature-pad__canvas');
    if (! canvas) return false;
    const ctx = canvas.getContext('2d');
    const data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
    // any non-white pixel with non-zero alpha counts as ink
    for (let i = 0; i < data.length; i += 4) {
      if (data[i + 3] > 0 && (data[i] < 250 || data[i + 1] < 250 || data[i + 2] < 250)) {
        return true;
      }
    }
    return false;
  });
  log('first canvas has ink after reload:', hasInk);
  if (! hasInk) fail('first signature canvas was BLANK after reload — restore did not work');

  // 10) Click "Discard draft" and confirm the localStorage entry is gone
  log('clicking Discard draft');
  const discardBtn = page.locator('button:has-text("Discard draft")');
  if (await discardBtn.count() === 0) {
    // It may have already hidden itself; the entry is still in localStorage.
    const keysBefore = await page.evaluate(() => Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.')));
    log('discard button already hidden. remaining keys:', keysBefore);
    if (keysBefore.length > 0) fail('discard button is hidden but draft is still in localStorage');
  } else {
    await discardBtn.first().click();
    await page.waitForLoadState('domcontentloaded');
    await wait(500);
    const keysAfter = await page.evaluate(() => Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.')));
    log('localStorage after discard:', keysAfter);
    if (keysAfter.length > 0) fail('draft was NOT cleared from localStorage');
  }

  log('ALL DRAFT CHECKS PASSED');
  await browser.close();
})().catch(e => fail(e.stack || e.message));
