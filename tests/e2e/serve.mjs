import { execFileSync, spawn } from 'node:child_process';
import { existsSync, writeFileSync } from 'node:fs';
import path from 'node:path';

const databasePath = process.env.DB_DATABASE || path.resolve('database/e2e.sqlite');
if (!existsSync(databasePath)) writeFileSync(databasePath, '');
const publicPath = path.resolve('public');
const serverScript = path.resolve('vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php');
const serverEnvironment = { ...process.env };

execFileSync('php', ['artisan', 'migrate:fresh', '--seed', '--seeder=E2eSeeder', '--force'], {
    cwd: process.cwd(),
    stdio: 'inherit',
    env: serverEnvironment,
});

const server = spawn('php', ['-S', '127.0.0.1:8001', serverScript], {
    cwd: publicPath,
    stdio: ['ignore', 'ignore', 'inherit'],
    env: serverEnvironment,
});

for (const signal of ['SIGINT', 'SIGTERM']) {
    process.on(signal, () => server.kill(signal));
}

server.on('exit', (code, signal) => {
    if (signal) process.stderr.write(`E2E server stopped by ${signal}.\n`);
    process.exit(code ?? (signal ? 1 : 0));
});
