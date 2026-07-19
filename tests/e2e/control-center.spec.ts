import { createHmac } from 'node:crypto';
import { expect, test, type Page } from '@playwright/test';

const controlCredentials = {
    email: 'e2e.control@peopleos.test',
    password: 'Control-E2E-2026!',
    secret: 'JBSWY3DPEHPK3PXP',
};

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

async function loginToControlCenter(page: Page) {
    await page.goto('/control-center/login');
    await page.getByLabel('Correo corporativo').fill(controlCredentials.email);
    await page.getByLabel('Contraseña').fill(controlCredentials.password);
    await page.getByRole('button', { name: 'Continuar con MFA' }).click();
    await expect(page).toHaveURL(/control-center\/mfa\/verificar$/);
    await page.getByLabel('Código de autenticación').fill(totp(controlCredentials.secret));
    await page.getByRole('button', { name: 'Verificar y continuar' }).click();
    await expect(page).toHaveURL(/\/control-center$/);
}

test.describe.serial('PeopleOS Control Center', () => {
    test('operador provisiona una empresa y el propietario activa su propia contraseña', async ({ page }) => {
        await loginToControlCenter(page);
        await expect(page.getByRole('heading', { name: 'Gobierno visible, acceso explícito.' })).toBeVisible();
        await expect(page.getByText('Cadena íntegra')).toBeVisible();
        if (process.env.VISUAL_QA) {
            await page.screenshot({ path: 'test-results/control-center-final.png', fullPage: true });
        }

        await page.getByRole('link', { name: 'Dar de alta empresa' }).click();
        await page.getByLabel('Nombre comercial').fill('Nébula Industrial');
        await page.getByLabel('Workspace').fill('nebula-industrial');
        await page.getByLabel('Razón social').fill('Nébula Industrial S.A.S.');
        await page.getByLabel('País').fill('CO');
        await page.getByLabel('Tipo fiscal').selectOption('NIT');
        await page.getByLabel('Identificación fiscal').fill('901456789-3');
        await page.getByLabel('Dirección registrada').fill('Calle 100 # 15-20, Bogotá');
        await page.getByText('Business', { exact: true }).click();
        await page.getByLabel('Puestos contratados').fill('350');
        await page.getByLabel('Nombre completo').fill('Valentina Propietaria');
        await page.getByLabel('Correo corporativo').fill('valentina@nebula.example');
        await page.getByRole('button', { name: 'Crear empresa y enviar invitación' }).click();

        await expect(page.getByText('Empresa creada en onboarding.')).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Nébula Industrial S.A.S.' })).toBeVisible();
        await expect(page.getByText('Preparación operativa')).toBeVisible();

        const invitationUrl = await page.locator('.cc-local-link a').getAttribute('href');
        expect(invitationUrl).toBeTruthy();
        await page.goto(invitationUrl!);
        await expect(page.getByRole('heading', { name: 'Toma control de tu espacio' })).toBeVisible();
        await page.getByLabel('Nueva contraseña').fill('Nebula-Owner-2026!');
        await page.getByLabel('Confirmar contraseña').fill('Nebula-Owner-2026!');
        await page.getByLabel(/Acepto la responsabilidad/).check();
        await page.getByRole('button', { name: 'Activar cuenta propietaria' }).click();
        await expect(page.getByRole('heading', { name: 'Tu cuenta ya está bajo tu control.' })).toBeVisible();

        await page.goto('/control-center');
        await page.getByRole('link', { name: 'Nébula Industrial', exact: true }).click();
        await expect(page.locator('.cc-readiness span').filter({ hasText: 'Propietario' })).toHaveClass(/is-ready/);
        await page.getByRole('button', { name: 'Marcar verificada' }).click();
        await expect(page.getByText('Entidad legal marcada como verificada.')).toBeVisible();
        await page.getByLabel('Nuevo dominio').fill('nebula.example');
        await page.getByRole('button', { name: 'Generar verificación' }).click();
        await expect(page.getByText('pos_domain_')).toBeVisible();
    });

    test('propietario de plataforma invita una identidad interna limitada', async ({ page }) => {
        await loginToControlCenter(page);
        await page.getByRole('link', { name: 'Equipo interno' }).click();
        await expect(page.getByRole('heading', { name: 'Nadie comparte una identidad privilegiada.' })).toBeVisible();
        await page.getByLabel('Nombre completo').fill('Soporte E2E');
        await page.getByLabel('Correo corporativo').fill('support.e2e@peopleos.test');
        await page.getByLabel('Función').selectOption('support');
        await page.getByRole('button', { name: 'Enviar invitación de 24 horas' }).click();
        await expect(page.getByText('Usuario interno invitado.')).toBeVisible();
        await expect(page.getByText('support.e2e@peopleos.test')).toBeVisible();
        await expect(page.getByText('MFA pendiente')).toBeVisible();
    });
});

test('Control Center móvil no desborda y respeta movimiento reducido', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await loginToControlCenter(page);
    await expect(page.getByRole('button', { name: 'Abrir navegación' })).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth)).toBeFalsy();
    expect(await page.locator('.cc-content > *').first().evaluate((element) => getComputedStyle(element).animationName)).toBe('none');
});

test('cerrar el navegador elimina la sesión del Control Center', async ({ browser }) => {
    let context = await browser.newContext({ baseURL: 'http://127.0.0.1:8001' });
    let page = await context.newPage();
    await loginToControlCenter(page);
    const session = (await context.cookies()).find((cookie) => cookie.name === 'peopleos_secure_session_v2');
    expect(session?.expires).toBe(-1);
    expect(session?.httpOnly).toBeTruthy();
    await context.close();

    context = await browser.newContext({ baseURL: 'http://127.0.0.1:8001' });
    page = await context.newPage();
    await page.goto('/control-center');
    await expect(page).toHaveURL(/\/control-center\/login$/);
    await context.close();
});
