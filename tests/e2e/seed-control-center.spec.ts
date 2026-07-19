import { expect, test } from '@playwright/test';
import { loginToControlCenter } from './audit/helpers';

test('seed control center', async ({ page }) => {
    await loginToControlCenter(page);
    await expect(page.getByRole('heading', { name: 'Gobierno visible, acceso explícito.' })).toBeVisible();
});
