import puppeteer from 'puppeteer-core';
import { mkdirSync } from 'fs';
import { accessSync, constants } from 'fs';
import { join } from 'path';

const OUT = 'C:\\Users\\eseyama\\AppData\\Local\\Temp\\opencode\\screenshots';
mkdirSync(OUT, { recursive: true });

let browserPath;
try { accessSync('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', constants.F_OK); browserPath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'; } catch {}
if (!browserPath) {
  try { accessSync('C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe', constants.F_OK); browserPath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe'; } catch {}
}

async function main() {
  console.log('Using browser:', browserPath);
  const browser = await puppeteer.launch({
    executablePath: browserPath,
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 1600 });

  const HTML_PATH = 'file:///C:/Users/eseyama/AppData/Local/Temp/opencode/before-after.html';

  // Full comparison
  await page.goto(HTML_PATH, { waitUntil: 'networkidle0', timeout: 20000 }).catch(() => {});
  await new Promise(r => setTimeout(r, 3000));
  await page.screenshot({ path: join(OUT, 'before-after-full.png'), fullPage: true });
  console.log('1/3 Saved before-after-full.png');

  // Reset, take before-only
  await page.reload({ waitUntil: 'networkidle0' }).catch(() => {});
  await new Promise(r => setTimeout(r, 2000));
  await page.evaluate(() => {
    document.querySelectorAll('.grid-cols-2 > div:last-child, .grid-cols-2 > div:last-child *').forEach(el => {
      if (el.style) el.style.setProperty('opacity', '0.15', 'important');
    });
  });
  await new Promise(r => setTimeout(r, 500));
  await page.screenshot({ path: join(OUT, 'before-only.png'), fullPage: true });
  console.log('2/3 Saved before-only.png');

  // Reset, take after-only
  await page.reload({ waitUntil: 'networkidle0' }).catch(() => {});
  await new Promise(r => setTimeout(r, 2000));
  await page.evaluate(() => {
    document.querySelectorAll('.grid-cols-2 > div:first-child, .grid-cols-2 > div:first-child *').forEach(el => {
      if (el.style) el.style.setProperty('opacity', '0.15', 'important');
    });
  });
  await new Promise(r => setTimeout(r, 500));
  await page.screenshot({ path: join(OUT, 'after-only.png'), fullPage: true });
  console.log('3/3 Saved after-only.png');

  await browser.close();
  console.log('Done!');
}

main().catch(e => { console.error(e); process.exit(1); });
