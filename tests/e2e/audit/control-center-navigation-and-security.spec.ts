import { expect, test } from '@playwright/test';
import {
    assertHealthyPage,
    collectBrowserEvidence,
    expectCleanBrowser,
    loginToControlCenter,
} from './helpers';

test('control-center-navigation-and-security', async ({ page }, testInfo) => {
    const evidence = collectBrowserEvidence(page);
    await loginToControlCenter(page);
    for (const path of ['/control-center', '/control-center/empresas/1', '/control-center/empresas/nueva', '/control-center/usuarios-internos', '/control-center/auditoria']) {
        await assertHealthyPage(page, path);
    }

    await page.goto('/admin/home');
    await expect(page).toHaveURL(/\/admin\/login$/);
    await page.goto('/control-center');
    await expect(page).toHaveURL(/\/control-center$/);

    await page.setViewportSize({ width: 390, height: 844 });
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await assertHealthyPage(page, '/control-center');
    expectCleanBrowser(evidence, testInfo);
});
