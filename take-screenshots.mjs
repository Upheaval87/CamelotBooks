import puppeteer from 'puppeteer-core';

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://127.0.0.1:8080';
const OUT = 'C:\\Users\\eseyama\\AppData\\Local\\Temp\\opencode\\screenshots';

import { mkdirSync, writeFileSync } from 'fs';
import { join } from 'path';

mkdirSync(OUT, { recursive: true });

async function shot(url, label) {
    console.log(`Taking ${label}...`);
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 900 });
    await page.goto(url, { waitUntil: 'networkidle0', timeout: 15000 }).catch(() => {});
    await new Promise(r => setTimeout(r, 2000));
    await page.screenshot({ path: join(OUT, `${label}.png`), fullPage: true });
    await browser.close();
    console.log(`  Saved ${label}.png`);
}

async function main() {
    // Pages that exist (not requiring login)
    await shot(BASE + '/login', 'before-login').catch(e => console.error('login fail:', e.message));
    console.log('Done');
}

main().catch(console.error);
