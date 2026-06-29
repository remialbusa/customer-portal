// Probe what the TSR page actually renders in the DOM
import { setTimeout as wait } from 'node:timers/promises';
import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: false, channel: 'msedge' });
const ctx = await browser.newContext();
const page = await ctx.newPage();

// login
await page.goto('http://127.0.0.1:8765/login', { waitUntil: 'domcontentloaded' });
await page.fill('input[name="email"]', 'remial.busa@mcbtsi.com');
await page.fill('input[name="password"]', 'Password!123');
await Promise.all([
  page.waitForURL(u => !u.toString().endsWith('/login'), { timeout: 20000 }),
  page.click('button[type="submit"]'),
]);
console.log('[probe] logged in, url =', page.url());

await page.goto('http://127.0.0.1:8765/tsp/tickets/2750538828', { waitUntil: 'domcontentloaded' });
await page.waitForSelector('canvas.signature-pad__canvas', { timeout: 20000 });
await wait(1500);

// Look for each field we expect on the form
const expected = [
  'problemAndConcerns', 'jobDone', 'partsReplaced', 'recommendation', 'remarks',
  'machineSystemSerialNumber', 'softwareVersionNo',
  'logInDate', 'serviceStartDateTime', 'serviceEndDateTime', 'logOutDate',
  'tspSignatureName', 'customerName', 'customerEmail', 'biomedName', 'biomedEmail',
  'tspWorkWithCsv', 'serviceStatus', 'email',
];

const found = await page.evaluate(() => {
  const els = [...document.querySelectorAll('input, textarea, select, [wire\\:model], [wire\\:model\\.live], [wire\\:model\\.blur]')];
  return els.map(e => ({
    tag: e.tagName,
    type: e.type || null,
    name: e.name || null,
    id: e.id || null,
    wireModel: e.getAttribute('wire:model') || e.getAttribute('wire:model.live') || e.getAttribute('wire:model.blur') || null,
    placeholder: (e.placeholder || '').slice(0, 40),
  }));
});

console.log('[probe] total form-like elements:', found.length);
console.log('[probe] all form elements:', JSON.stringify(found, null, 2));

// Also dump the fieldset count
const fieldsets = await page.evaluate(() => {
  return [...document.querySelectorAll('fieldset.tsr-section')].map(f => {
    const legend = f.querySelector('legend')?.innerText?.trim() || '';
    const inputs = f.querySelectorAll('input, textarea, select').length;
    return { legend, inputCount: inputs };
  });
});
console.log('[probe] fieldsets on page:', JSON.stringify(fieldsets, null, 2));

// count fields missing
const foundModels = new Set(found.map(e => e.wireModel).filter(Boolean));
const missing = expected.filter(m => !foundModels.has(m));
console.log('[probe] expected but missing wire:model:', missing);

await new Promise(r => page.on('close', r));
await browser.close();
