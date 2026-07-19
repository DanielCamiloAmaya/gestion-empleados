import { execFileSync } from 'node:child_process';
import { existsSync, writeFileSync } from 'node:fs';
import path from 'node:path';

export default async function globalSetup() {
    const databasePath = path.resolve('database/e2e.sqlite');

    if (!existsSync(databasePath)) {
        writeFileSync(databasePath, '');
    }

    execFileSync('php', ['artisan', 'migrate:fresh', '--seed', '--seeder=E2eSeeder', '--force'], {
        cwd: process.cwd(),
        stdio: 'inherit',
        env: {
            ...process.env,
            APP_ENV: 'testing',
            APP_KEY: 'base64:CZNGUWf/tfXzPUunVmzDl6OPfABeze9i7t6YsoRzgsI=',
            DB_CONNECTION: 'sqlite',
            DB_DATABASE: databasePath,
            SESSION_DRIVER: 'file',
            CACHE_STORE: 'array',
            QUEUE_CONNECTION: 'sync',
        },
    });
}
