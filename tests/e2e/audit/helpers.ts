import { createHmac } from 'node:crypto';
import { expect, type Page, type TestInfo } from '@playwright/test';

export const ownerCredentials = {
    email: 'e2e.owner@peopleos.test',
    password: 'PeopleOS-Owner-2026!',
};

export const employeeCredentials = {
    username: 'e2e.employee',
    password: 'Employee-E2E-2026!',
};

export const controlCredentials = {
    email: 'e2e.control@peopleos.test',
    password: 'Control-E2E-2026!',
    secret: 'JBSWY3DPEHPK3PXP',
};

export type BrowserEvidence = {
    consoleErrors: string[];
    pageErrors: string[];
    serverErrors: string[];
    failedRequests: string[];
};

export function collectBrowserEvidence(page: Page): BrowserEvidence {
    const evidence: BrowserEvidence = {
        consoleErrors: [],
        pageErrors: [],
        serverErrors: [],
        failedRequests: [],
    };
    page.on('console', (message) => {
        if (message.type() === 'error') {
            const location = message.location();
            const suffix = location.url ? ` (${location.url}:${location.lineNumber}:${location.columnNumber})` : '';
            evidence.consoleErrors.push(`${message.text()}${suffix}`);
        }
    });
    page.on('pageerror', (error) => evidence.pageErrors.push(error.message));
    page.on('response', (response) => {
        if (response.status() >= 500) evidence.serverErrors.push(`${response.status()} ${response.url()}`);
    });
    page.on('requestfailed', (request) => evidence.failedRequests.push(`${request.failure()?.errorText} ${request.url()}`));
    return evidence;
}

export function expectCleanBrowser(evidence: BrowserEvidence, testInfo?: TestInfo) {
    if (testInfo) {
        testInfo.attach('browser-evidence.json', {
            body: Buffer.from(JSON.stringify(evidence, null, 2)),
            contentType: 'application/json',
        });
    }
    expect(evidence.consoleErrors, 'browser console errors').toEqual([]);
    expect(evidence.pageErrors, 'uncaught page errors').toEqual([]);
    expect(evidence.serverErrors, 'HTTP 5xx responses').toEqual([]);
    expect(evidence.failedRequests, 'failed browser requests').toEqual([]);
}

export async function loginAsOwner(page: Page) {
    await page.goto('/admin/login');
    await page.getByLabel('Nombre o correo corporativo').fill(ownerCredentials.email);
    await page.getByLabel('Contraseña').fill(ownerCredentials.password);
    await page.getByRole('button', { name: 'Acceder al panel' }).click();
    await expect(page).toHaveURL(/\/admin\/home$/);
}

export async function loginAsEmployee(page: Page) {
    await page.goto('/login');
    await page.getByLabel('Usuario o correo corporativo').fill(employeeCredentials.username);
    await page.getByLabel('Contraseña').fill(employeeCredentials.password);
    await page.getByRole('button', { name: 'Entrar a PeopleOS' }).click();
    await expect(page).toHaveURL(/\/home$/);
}

export async function loginToControlCenter(page: Page) {
    await page.goto('/control-center/login');
    await page.getByLabel('Correo corporativo').fill(controlCredentials.email);
    await page.getByLabel('Contraseña').fill(controlCredentials.password);
    await page.getByRole('button', { name: 'Continuar con MFA' }).click();
    await page.getByLabel('Código de autenticación').fill(totp(controlCredentials.secret));
    await page.getByRole('button', { name: 'Verificar y continuar' }).click();
    await expect(page).toHaveURL(/\/control-center$/);
}

export async function assertHealthyPage(page: Page, path: string) {
    const response = await page.goto(path);
    expect(response, `navigation response for ${path}`).not.toBeNull();
    expect(response!.status(), `HTTP status for ${path}`).toBeLessThan(400);
    await expect(page.locator('main h1').first(), `primary heading for ${path}`).toBeVisible();
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth);
    expect(overflow, `horizontal overflow for ${path}`).toBeFalsy();
}

export type PageAuditIssue = {
    path: string;
    kind: 'navigation' | 'http' | 'heading' | 'overflow' | 'console' | 'exception';
    detail: string;
};

export async function auditPage(page: Page, path: string): Promise<PageAuditIssue[]> {
    const issues: PageAuditIssue[] = [];
    try {
        const response = await page.goto(path);
        if (!response) {
            issues.push({ path, kind: 'navigation', detail: 'La navegación no produjo una respuesta HTTP.' });
            return issues;
        }
        if (response.status() >= 400) {
            issues.push({ path, kind: 'http', detail: `HTTP ${response.status()}` });
            return issues;
        }

        if (!await page.locator('main h1').first().isVisible().catch(() => false)) {
            issues.push({ path, kind: 'heading', detail: 'No existe un encabezado principal visible dentro de <main>.' });
        }
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth);
        if (overflow) {
            issues.push({
                path,
                kind: 'overflow',
                detail: `scrollWidth=${await page.evaluate(() => document.documentElement.scrollWidth)}, clientWidth=${await page.evaluate(() => document.documentElement.clientWidth)}`,
            });
        }
    } catch (error) {
        issues.push({
            path,
            kind: 'exception',
            detail: error instanceof Error ? error.message : String(error),
        });
    }
    return issues;
}

function decodeBase32(value: string): Buffer {
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    let bits = '';
    for (const character of value.replace(/=+$/, '').toUpperCase()) {
        bits += alphabet.indexOf(character).toString(2).padStart(5, '0');
    }
    const bytes: number[] = [];
    for (let index = 0; index + 8 <= bits.length; index += 8) {
        bytes.push(Number.parseInt(bits.slice(index, index + 8), 2));
    }
    return Buffer.from(bytes);
}

function totp(secret: string): string {
    const counter = Math.floor(Date.now() / 1000 / 30);
    const buffer = Buffer.alloc(8);
    buffer.writeBigUInt64BE(BigInt(counter));
    const digest = createHmac('sha1', decodeBase32(secret)).update(buffer).digest();
    const offset = digest[digest.length - 1] & 0x0f;
    const value = (
        ((digest[offset] & 0x7f) << 24)
        | ((digest[offset + 1] & 0xff) << 16)
        | ((digest[offset + 2] & 0xff) << 8)
        | (digest[offset + 3] & 0xff)
    ) % 1_000_000;
    return value.toString().padStart(6, '0');
}
