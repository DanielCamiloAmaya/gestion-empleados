import { expect, test } from '@playwright/test';
import { collectBrowserEvidence, loginAsOwner } from './helpers';

test.describe.serial('governance and talent workflows', () => {
    test('owner creates a granular role, adjusts leave balance, and creates a one-time API token', async ({ page }) => {
        const evidence = collectBrowserEvidence(page);
        await loginAsOwner(page);

        await page.goto('/admin/accesos');
        const roleForm = page.locator('form[action$="/admin/accesos/roles"]');
        await roleForm.locator('[name="name"]').fill('Auditor E2E');
        await roleForm.locator('[name="description"]').fill('Rol limitado creado durante la auditoría integral.');
        await roleForm.locator('[name="permissions[]"]').first().check();
        await roleForm.getByRole('button', { name: 'Crear rol' }).click();
        await expect(page.getByRole('heading', { name: 'Auditor E2E', exact: true })).toBeVisible();

        await page.goto('/admin/ausencias/configuracion');
        const balanceForm = page.locator('form[action$="/admin/ausencias/saldos"]');
        await balanceForm.locator('[name="allocated"]').fill('18');
        await balanceForm.getByRole('button', { name: 'Guardar saldo' }).click();
        await expect(page.getByText(/Saldo actualizado/)).toBeVisible();

        await page.goto('/admin/plataforma');
        const tokenForm = page.locator('form[action$="/admin/plataforma/tokens"]');
        await tokenForm.locator('[name="name"]').fill('Auditoría E2E');
        await tokenForm.locator('[name="abilities[]"][value="employees:read"]').check();
        await tokenForm.locator('[name="expires_in_days"]').fill('30');
        await tokenForm.getByRole('button', { name: 'Generar token' }).click();
        await expect(page.locator('.secret-reveal code')).toHaveCount(1);
        await expect(page.getByText('Auditoría E2E')).toBeVisible();

        expect(evidence.pageErrors).toEqual([]);
        expect(evidence.serverErrors).toEqual([]);
        expect(evidence.failedRequests).toEqual([]);
    });

    test('owner persists recruiting, learning, and compensation records through the UI', async ({ page }) => {
        const evidence = collectBrowserEvidence(page);
        await loginAsOwner(page);
        await page.goto('/talento-plus');

        const jobForm = page.locator('form[action$="/admin/talento-plus/vacantes"]');
        await jobForm.locator('[name="title"]').fill('Arquitecto de Plataforma E2E');
        await jobForm.locator('[name="location"]').fill('Bogotá · Híbrido');
        await jobForm.locator('[name="description"]').fill('Diseña una plataforma empresarial segura, observable, escalable y preparada para múltiples regiones.');
        await jobForm.getByRole('button', { name: 'Publicar vacante' }).click();
        await expect(page.getByText('Arquitecto de Plataforma E2E')).toBeVisible();

        const jobCard = page.locator('.pipeline article').filter({ hasText: 'Arquitecto de Plataforma E2E' });
        await jobCard.locator('[name="name"]').fill('Candidata E2E');
        await jobCard.locator('[name="email"]').fill('candidata.e2e@example.com');
        await jobCard.getByRole('button', { name: 'Agregar' }).click();
        await expect(page.getByText('Candidata E2E', { exact: true })).toBeVisible();

        const courseForm = page.locator('form[action$="/admin/talento-plus/cursos"]');
        await courseForm.locator('[name="title"]').fill('Seguridad empresarial E2E');
        await courseForm.locator('[name="provider"]').fill('PeopleOS Academy');
        await courseForm.locator('[name="duration_minutes"]').fill('90');
        await courseForm.locator('[name="description"]').fill('Controles de seguridad, respuesta a incidentes y continuidad.');
        await courseForm.locator('[name="is_mandatory"]').check();
        await courseForm.getByRole('button', { name: /Agregar al/ }).click();
        await expect(page.getByText('Seguridad empresarial E2E')).toBeVisible();

        const compensationForm = page.locator('form[action$="/admin/talento-plus/compensacion"]');
        await compensationForm.locator('[name="base_salary"]').fill('12000000');
        await compensationForm.locator('[name="currency"]').fill('COP');
        await compensationForm.locator('[name="variable_target"]').fill('10');
        await compensationForm.locator('[name="pay_grade"]').fill('P5');
        await compensationForm.locator('[name="effective_from"]').fill('2026-08-01');
        await compensationForm.getByRole('button', { name: 'Registrar vigencia' }).click();
        await expect(page.getByText(/Compensación registrada/)).toBeVisible();

        expect(evidence.consoleErrors).toEqual([]);
        expect(evidence.pageErrors).toEqual([]);
        expect(evidence.serverErrors).toEqual([]);
        expect(evidence.failedRequests).toEqual([]);
    });
});
