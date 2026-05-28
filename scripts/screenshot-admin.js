import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

(async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();
    
    // 访问登录页
    await page.goto('http://127.0.0.1:8000/admin/login', { 
        waitUntil: 'networkidle',
        timeout: 30000 
    });
    
    // 填写登录表单（使用默认凭据）
    await page.fill('input[type="email"]', 'admin@example.com');
    await page.fill('input[type="password"]', 'password');
    await page.click('button[type="submit"]');
    
    // 等待跳转到管理后台
    await page.waitForURL('http://127.0.0.1:8000/admin', { timeout: 10000 });
    await page.waitForLoadState('networkidle');
    
    // 截图
    await page.screenshot({ 
        path: path.join(__dirname, 'admin-dashboard.png'),
        fullPage: false 
    });
    console.log('Dashboard screenshot saved');
    
    await browser.close();
})();