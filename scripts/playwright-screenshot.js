import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

(async () => {
    const browser = await chromium.launch();
    
    // 桌面端截图
    const desktopContext = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const desktopPage = await desktopContext.newPage();
    await desktopPage.goto('http://127.0.0.1:8000/admin/login', { 
        waitUntil: 'networkidle',
        timeout: 30000 
    });
    await desktopPage.screenshot({ 
        path: path.join(__dirname, 'login-desktop.png'),
        fullPage: true 
    });
    console.log('Desktop screenshot saved: login-desktop.png');
    
    // 移动端截图
    const mobileContext = await browser.newContext({
        viewport: { width: 375, height: 812 }
    });
    const mobilePage = await mobileContext.newPage();
    await mobilePage.goto('http://127.0.0.1:8000/admin/login', { 
        waitUntil: 'networkidle',
        timeout: 30000 
    });
    await mobilePage.screenshot({ 
        path: path.join(__dirname, 'login-mobile.png'),
        fullPage: true 
    });
    console.log('Mobile screenshot saved: login-mobile.png');
    
    await browser.close();
})();