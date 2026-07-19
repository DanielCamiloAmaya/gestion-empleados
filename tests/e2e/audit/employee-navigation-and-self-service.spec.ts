import { expect, test } from '@playwright/test';
import {
    assertHealthyPage,
    auditPage,
    collectBrowserEvidence,
    expectCleanBrowser,
    loginAsEmployee,
} from './helpers';

test('employee-navigation-and-self-service', async ({ page }, testInfo) => {
    const evidence = collectBrowserEvidence(page);
    await loginAsEmployee(page);
    const paths = [
        '/home',
        '/empleados',
        '/departamentos',
        '/aprobaciones',
        '/solicitudes',
        '/solicitudes/nueva',
        '/onboarding',
        '/objetivos',
        '/documentos',
        '/evaluaciones',
        '/talento-plus',
        '/seguridad/mfa',
        '/notificaciones',
    ];
    const issues = [];
    for (const path of paths) {
        const consoleCount = evidence.consoleErrors.length;
        issues.push(...await auditPage(page, path));
        for (const detail of evidence.consoleErrors.slice(consoleCount)) {
            issues.push({ path, kind: 'console' as const, detail });
        }
    }
    await testInfo.attach('employee-navigation-audit.json', {
        body: Buffer.from(JSON.stringify({ paths, issues }, null, 2)),
        contentType: 'application/json',
    });

    await page.goto('/empleados');
    await expect(page.getByRole('link', { name: 'Incorporar persona' })).toHaveCount(0);
    await page.goto('/departamentos');
    await expect(page.getByRole('link', { name: 'Nuevo departamento' })).toHaveCount(0);

    await page.setViewportSize({ width: 390, height: 844 });
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await assertHealthyPage(page, '/home');
    const animation = await page.locator('.reveal-item').first().evaluate((element) => getComputedStyle(element).animationName);
    expect(animation).toBe('none');
    expect(issues, 'employee navigation audit').toEqual([]);
    expectCleanBrowser(evidence, testInfo);
});
