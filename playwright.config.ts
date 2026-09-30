import { defineConfig, devices } from '@playwright/test';
export default defineConfig({
    testDir: './tests/Browser',
    fullyParallel: false,
    workers: 1,
    expect: { timeout: 15000 },
    reporter: [
        ['list'],
        ['html', { open: 'never', outputFolder: '.tools/playwright-report' }],
    ],
    use: { baseURL: 'http://127.0.0.1:8000', trace: 'retain-on-failure' },
    projects: [
        {
            name: 'desktop',
            use: {
                ...devices['Desktop Chrome'],
                viewport: { width: 1280, height: 900 },
            },
        },
        {
            name: 'mobile',
            use: {
                ...devices['Desktop Chrome'],
                viewport: { width: 360, height: 800 },
            },
        },
    ],
});
