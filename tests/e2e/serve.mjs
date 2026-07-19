import { execFileSync, spawn } from 'node:child_process';
import { existsSync, writeFileSync } from 'node:fs';
import path from 'node:path';

const databasePath = process.env.DB_DATABASE || path.resolve('database/e2e.sqlite');
if (!existsSync(databasePath)) writeFileSync(databasePath, '');

execFileSync('php', ['artisan', 'migrate:fresh', '--seed', '--seeder=E2eSeeder', '--force'], {
    cwd: process.cwd(),
    stdio: 'inherit',
    env: process.env,
});

const server = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', '--port=8001', '--no-reload'], {
    cwd: process.cwd(),
    stdio: 'inherit',
    env: process.env,
});

for (const signal of ['SIGINT', 'SIGTERM']) {
    process.on(signal, () => server.kill(signal));
}

server.on('exit', (code) => process.exit(code ?? 0));
