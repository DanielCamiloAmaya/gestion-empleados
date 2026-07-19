import { expect, test } from '@playwright/test';
import { loginAsOwner } from './audit/helpers';

test('seed tenant owner', async ({ page }) => {
    await loginAsOwner(page);
    await expect(page.getByRole('heading', { name: 'Resumen de personas' })).toBeVisible();
});
