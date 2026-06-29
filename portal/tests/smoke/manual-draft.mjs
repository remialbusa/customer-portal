// Manual verification of draft autosave: drives Edge/Chromium, types into
// the form, draws on the signature pads, reloads, and checks that
// everything comes back. Prints all intermediate state so a human can
// confirm. Stays open at the end so the operator can poke at it.
import { setTimeout as wait } from 'node:timers/promises';
import { chromium } from 'playwright';

const BASE  = 'http://127.0.0.1:8765';
const EMAIL = 'remial.busa@mcbtsi.com';
const PASSWORD = 'Password!123';
const TICKET = '2750538828';

const log = (...args) => console.log('[manual]', ...args);

const browser = await chromium.launch({
  headless: false,                          // show the window
  channel: 'msedge',                        // Edge is installed
  args: ['--no-sandbox', '--disable-dev-shm-usage'],
});
const ctx = await browser.newContext({
  viewport: { width: 1400, height: 1000 },
  locale: 'en-US',
});
const page = await ctx.newPage();
page.on('pageerror', e => console.log('[pageerror]', e.message));
page.on('console',  m => { if (m.type() === 'error' || m.type() === 'warning') console.log('[' + m.type() + ']', m.text()); });

// Step 1: log in
log('opening login page...');
await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
await page.fill('input[name="email"]', EMAIL);
await page.fill('input[name="password"]', PASSWORD);
await Promise.all([
  page.waitForURL(u => !u.toString().endsWith('/login'), { timeout: 20000 }),
  page.click('button[type="submit"]'),
]);
log('logged in, url =', page.url());

// Step 2: go straight to the ticket show page (where the TSR form lives)
log('opening ticket show page...');
await page.goto(`${BASE}/tsp/tickets/${TICKET}`, { waitUntil: 'domcontentloaded' });
await page.waitForSelector('canvas.signature-pad__canvas', { timeout: 20000 });
await wait(1000);

// Wipe any prior draft so we start clean
await page.evaluate(() => {
  Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.')).forEach(k => localStorage.removeItem(k));
  return Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.'));
});
log('cleared any prior drafts.');

// Step 3: scroll to the form, fill text fields
log('scrolling to the TSR form...');
await page.locator('canvas.signature-pad__canvas').first().scrollIntoViewIfNeeded();
await wait(300);

log('typing into tspSignatureName...');
await page.locator('input[wire\\:model="tspSignatureName"]').fill('My Test FSE');
log('typing into customerName...');
await page.locator('input[wire\\:model="customerName"]').fill('Jane Customer');
log('typing into customerEmail...');
await page.locator('input[wire\\:model="customerEmail"]').fill('jane@example.com');
log('typing into biomedName...');
await page.locator('input[wire\\:model="biomedName"]').fill('Bob Biomed');
log('typing into biomedEmail...');
await page.locator('input[wire\\:model="biomedEmail"]').fill('bob@example.com');

await wait(1500); // let the 400ms debounced save fire

// Step 4: draw a simple signature on the FIRST pad
log('drawing on the first signature pad...');
const firstPad = page.locator('canvas.signature-pad__canvas').first();
await firstPad.scrollIntoViewIfNeeded();
await wait(200);
const box = await firstPad.boundingBox();
await page.mouse.move(box.x + 30, box.y + box.height / 2);
await page.mouse.down();
for (let k = 0; k <= 20; k++) {
  await page.mouse.move(
    box.x + 30 + (box.width - 60) * (k / 20),
    box.y + box.height / 2 + Math.sin(k * 0.6) * (box.height / 4),
    { steps: 3 }
  );
}
await page.mouse.up();
await wait(800);

// Step 5: also draw on the customer signature pad
log('drawing on the customer signature pad...');
const customerPad = page.locator('canvas.signature-pad__canvas').nth(1);
await customerPad.scrollIntoViewIfNeeded();
await wait(200);
const cbox = await customerPad.boundingBox();
await page.mouse.move(cbox.x + 30, cbox.y + cbox.height / 2);
await page.mouse.down();
for (let k = 0; k <= 14; k++) {
  await page.mouse.move(
    cbox.x + 30 + (cbox.width - 60) * (k / 14),
    cbox.y + cbox.height / 2 - Math.cos(k * 0.7) * (cbox.height / 4),
    { steps: 3 }
  );
}
await page.mouse.up();
await wait(1500); // let the signature commit + debounce save fire

// Dump the current state of localStorage so we can see what was saved
const draftState = await page.evaluate(() => {
  const keys = Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.'));
  return keys.map(k => {
    const v = JSON.parse(localStorage.getItem(k) || '{}');
    return {
      key: k,
      fields: Object.fromEntries(
        Object.entries(v.fields || {}).map(([kk, vv]) => [kk, typeof vv === 'string' && vv.length > 30 ? vv.slice(0, 30) + '...(' + vv.length + ')' : vv])
      ),
      signatures: Object.fromEntries(
        Object.entries(v.signatures || {}).map(([kk, vv]) => [kk, typeof vv === 'string' ? (vv.length + ' chars') : vv])
      ),
      savedAt: v.savedAt,
    };
  });
});
log('saved draft:', JSON.stringify(draftState, null, 2));

// Step 6: reload the page so we can verify restore
log('reloading the page...');
await page.reload({ waitUntil: 'domcontentloaded' });
await page.waitForSelector('canvas.signature-pad__canvas', { timeout: 20000 });
await wait(2000); // let Alpine init and hydrate

// Check that fields came back
const restored = await page.evaluate(() => {
  const get = sel => document.querySelector(sel)?.value || null;
  const hidden = name => document.querySelector(`input[name="${name}"]`)?.value || null;
  // Inspect the first canvas's pixels to see if any ink was restored
  const c = document.querySelector('canvas.signature-pad__canvas');
  const ctx = c.getContext('2d');
  const img = ctx.getImageData(0, 0, c.width, c.height).data;
  let nonBlank = 0;
  for (let i = 3; i < img.length; i += 4) if (img[i] > 0) nonBlank++;
  return {
    tspSignatureName:     get('input[wire\\:model="tspSignatureName"]'),
    customerName:         get('input[wire\\:model="customerName"]'),
    customerEmail:        get('input[wire\\:model="customerEmail"]'),
    biomedName:           get('input[wire\\:model="biomedName"]'),
    biomedEmail:          get('input[wire\\:model="biomedEmail"]'),
    tspSigHiddenLen:      (hidden('tspSignatureDataUrl')      || '').length,
    customerSigHiddenLen: (hidden('customerSignatureDataUrl') || '').length,
    firstCanvasNonBlankPixels: nonBlank,
    localStorageKeys: Object.keys(localStorage).filter(k => k.startsWith('tsr.draft.')),
  };
});
log('restored state:', JSON.stringify(restored, null, 2));

// Step 7: check for the "draft restored" banner / discard button
const banner = await page.locator('text=Restore draft').count();
const discard = await page.locator('button:has-text("Discard draft")').count();
log('restore banner present:', banner > 0, '; discard button present:', discard > 0);

// Final summary
const allOk = (
  restored.tspSignatureName === 'My Test FSE' &&
  restored.customerName === 'Jane Customer' &&
  restored.customerEmail === 'jane@example.com' &&
  restored.biomedName === 'Bob Biomed' &&
  restored.biomedEmail === 'bob@example.com' &&
  restored.tspSigHiddenLen > 100 &&
  restored.customerSigHiddenLen > 100 &&
  restored.firstCanvasNonBlankPixels > 0
);
log('all checks pass:', allOk);
log('');
log('The browser is staying open. Click around, refresh manually,');
log('test the Discard button, etc. Close it when you\'re done.');

// Keep the page open until the user closes it
await new Promise(r => {
  page.on('close', r);
});

await browser.close();
