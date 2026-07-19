import { defineConfig, devices } from '@playwright/test';
import path from 'node:path';

const databasePath = path.resolve('database/e2e.sqlite');
const applicationEnvironment = {
    APP_ENV: 'testing',
    APP_KEY: 'base64:CZNGUWf/tfXzPUunVmzDl6OPfABeze9i7t6YsoRzgsI=',
    APP_DEBUG: 'false',
    APP_MAINTENANCE_DRIVER: 'file',
    BCRYPT_ROUNDS: '4',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: databasePath,
    SESSION_DRIVER: 'file',
    CACHE_STORE: 'array',
    QUEUE_CONNECTION: 'sync',
};

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    timeout: 45_000,
    expect: { timeout: 8_000 },
    reporter: [['list']],
    use: {
        baseURL: 'http://127.0.0.1:8001',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    webServer: {
        command: 'node tests/e2e/serve.mjs',
        url: 'http://127.0.0.1:8001/login',
        reuseExistingServer: false,
        timeout: 60_000,
        env: applicationEnvironment,
    },
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    ],
});
