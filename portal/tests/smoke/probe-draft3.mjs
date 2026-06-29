// Probe: list all forms and their submit handlers.
import { chromium } from 'playwright';

const BASE = 'http://127.0.0.1:8765';
const EMAIL = 'remial.busa@mcbtsi.com';
const PASSWORD = 'Password!123';
const TICKET = '2750538828';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();
page.on('pageerror', e => console.log('[pageerror]', e.message));
page.on('console',  m => console.log('[console.' + m.type() + ']', m.text()));

await page.goto(`${BASE}/login`);
await page.fill('input[name="email"]', EMAIL);
await page.fill('input[name="password"]', PASSWORD);
await Promise.all([
  page.waitForURL(u => !u.toString().endsWith('/login'), { timeout: 15000 }),
  page.click('button[type="submit"]'),
]);

await page.goto(`${BASE}/tsp/tickets/${TICKET}`, { waitUntil: 'domcontentloaded' });
await page.waitForSelector('canvas.signature-pad__canvas', { timeout: 20000 });

const forms = await page.evaluate(() => {
  return Array.from(document.querySelectorAll('form')).map(f => ({
    action: f.getAttribute('wire:submit') || f.action,
    classes: f.className,
    parentClasses: f.parentElement?.className || '',
    inputCount: f.querySelectorAll('input,textarea,select').length,
    hasSignature: !! f.querySelector('canvas'),
    submitText: Array.from(f.querySelectorAll('button[type="submit"]')).map(b => b.textContent.trim()).join(' | '),
    legendText: Array.from(f.querySelectorAll('legend')).map(l => l.textContent.trim()).join(' | ').substring(0, 100),
  }));
});
console.log('FORMS:', JSON.stringify(forms, null, 2));

// Look for the TSR form by its fieldset structure
const tsrFormByCanvas = await page.evaluate(() => {
  const canvas = document.querySelector('canvas.signature-pad__canvas');
  if (! canvas) return null;
  let el = canvas;
  while (el && el.tagName !== 'FORM') el = el.parentElement;
  if (! el) return null;
  return {
    action: el.getAttribute('wire:submit'),
    inputs: Array.from(el.querySelectorAll('input,textarea,select')).map(i => ({
      type: i.type, model: i.getAttribute('wire:model'), name: i.name
    })),
  };
});
console.log('TSR FORM (found via canvas):', JSON.stringify(tsrFormByCanvas, null, 2));

await browser.close();
