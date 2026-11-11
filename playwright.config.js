import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/browser',
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    use: {
        baseURL: process.env.DEMO_URL || 'http://127.0.0.1:8001',
        viewport: { width: 1440, height: 1000 },
        trace: 'retain-on-failure',
        launchOptions: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH
            ? { executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH }
            : {},
    },
});
