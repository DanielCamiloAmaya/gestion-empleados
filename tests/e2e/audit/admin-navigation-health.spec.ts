import { expect, test } from '@playwright/test';
import {
    auditPage,
    collectBrowserEvidence,
    expectCleanBrowser,
    loginAsOwner,
} from './helpers';

test('admin-navigation-health', async ({ page }, testInfo) => {
    const evidence = collectBrowserEvidence(page);
    await loginAsOwner(page);

    const paths = [
        '/admin/home',
        '/empleados',
        '/admin/empleados/nuevo',
        '/admin/empleados/importar',
        '/departamentos',
        '/admin/departamentos/nuevo',
        '/aprobaciones',
        '/solicitudes',
        '/admin/ausencias/configuracion',
        '/onboarding',
        '/admin/onboarding/nueva',
        '/objetivos',
        '/admin/objetivos/nuevo',
        '/documentos',
        '/evaluaciones',
        '/talento-plus',
        '/admin/auditoria',
        '/admin/offboarding',
        '/admin/reportes',
        '/seguridad/mfa',
        '/admin/plataforma',
        '/admin/accesos',
        '/admin/accesos/soporte',
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
    await testInfo.attach('admin-navigation-audit.json', {
        body: Buffer.from(JSON.stringify({ paths, issues }, null, 2)),
        contentType: 'application/json',
    });
    expect(issues, 'admin navigation audit').toEqual([]);
    expectCleanBrowser(evidence, testInfo);
});
