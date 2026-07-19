import { expect, test } from '@playwright/test';
import { loginAsOwner } from './helpers';

test('API token with a finite expiration is created without a server error', async ({ page }) => {
    await loginAsOwner(page);
    await page.goto('/admin/plataforma');
    const form = page.locator('form[action$="/admin/plataforma/tokens"]');
    await form.locator('[name="name"]').fill('Token con vencimiento E2E');
    await form.locator('[name="abilities[]"][value="employees:read"]').check();
    await form.locator('[name="expires_in_days"]').fill('30');
    const navigation = page.waitForResponse((response) => response.url().endsWith('/admin/plataforma/tokens'));
    await form.getByRole('button', { name: 'Generar token' }).click();
    expect((await navigation).status(), 'token creation response').toBeLessThan(400);
    await expect(page.locator('.secret-reveal code')).toHaveCount(1);
});
