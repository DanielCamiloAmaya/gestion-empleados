import { expect, test } from '@playwright/test';
import { loginAsEmployee } from './audit/helpers';

test('seed employee', async ({ page }) => {
    await loginAsEmployee(page);
    await expect(page.getByRole('heading', { name: /Hola, E2E/ })).toBeVisible();
});
