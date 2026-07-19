import { expect, test, type Page } from '@playwright/test';
import path from 'node:path';

const employeeCredentials = { username: 'e2e.employee', password: 'Employee-E2E-2026!' };
const adminCredentials = { email: 'e2e.admin@peopleos.test', password: 'PeopleOS-E2E-2026!' };

async function loginAsAdmin(page: Page) {
    await page.goto('/admin/login');
    await page.getByLabel('Nombre o correo corporativo').fill(adminCredentials.email);
    await page.getByLabel('Contraseña').fill(adminCredentials.password);
    await page.getByRole('button', { name: 'Acceder al panel' }).click();
    await expect(page).toHaveURL(/\/admin\/home$/);
}

async function loginAsEmployee(page: Page) {
    await page.goto('/login');
    await page.getByLabel('Usuario o correo corporativo').fill(employeeCredentials.username);
    await page.getByLabel('Contraseña').fill(employeeCredentials.password);
    await page.getByRole('button', { name: 'Entrar a PeopleOS' }).click();
    await expect(page).toHaveURL(/\/home$/);
}

async function logout(page: Page) {
    await page.getByRole('button', { name: 'Cerrar sesión' }).click();
    await expect(page).toHaveURL(/\/login$/);
}

test.describe.serial('ciclo de vida operativo de una persona', () => {
    test('RR. HH. crea estructura y una persona con trazabilidad', async ({ page }) => {
        await loginAsAdmin(page);
        await expect(page.getByRole('region', { name: 'Pulso operativo de personas' })).toBeVisible();

        await page.getByRole('link', { name: 'Nuevo departamento' }).click();
        await page.getByLabel('Nombre').fill('Customer Success E2E');
        await page.getByLabel('Centro de costo').fill('CC-E2E');
        await page.getByLabel('Descripción').fill('Área creada por la validación integral de navegador.');
        await page.getByRole('button', { name: 'Crear departamento' }).click();
        await expect(page.getByText('Departamento Customer Success E2E creado.')).toBeVisible();

        await page.getByRole('navigation', { name: 'Navegación principal' }).getByRole('link', { name: 'Personas' }).click();
        await page.getByRole('link', { name: 'Incorporar persona' }).click();
        await page.getByLabel('Código de empleado').fill('E2E-002');
        await page.getByLabel('Cargo').fill('Customer Success Manager');
        await page.getByLabel('Nombres').fill('Laura');
        await page.getByLabel('Apellidos').fill('Automatización');
        await page.getByLabel('Correo corporativo').fill('laura.e2e@peopleos.test');
        await page.getByLabel('Nombre de usuario').fill('laura.e2e');
        await page.getByLabel('Departamento').selectOption({ label: 'Customer Success E2E' });
        await page.getByLabel('Estado laboral').selectOption('active');
        await page.getByLabel('Tipo de vinculación').selectOption('full_time');
        await page.getByLabel('Sede o ubicación').fill('Medellín · Remoto');
        await page.getByLabel('Contraseña temporal').fill('Laura-E2E-2026!');
        await page.getByLabel('Confirmar contraseña').fill('Laura-E2E-2026!');
        await page.getByRole('button', { name: 'Crear empleado' }).click();
        await expect(page.getByRole('heading', { name: 'Laura Automatización' })).toBeVisible();

        await page.getByRole('navigation', { name: 'Navegación principal' }).getByRole('link', { name: 'Auditoría' }).click();
        await expect(page.getByRole('cell', { name: 'incorporó una persona' }).first()).toBeVisible();
        await logout(page);
    });

    test('empleado solicita una ausencia desde su portal', async ({ page }) => {
        await loginAsEmployee(page);
        await expect(page.getByRole('heading', { name: 'Hola, E2E.' })).toBeVisible();
        await expect(page.getByRole('link', { name: /Onboarding/ }).first()).toBeVisible();

        await page.getByRole('link', { name: 'Solicitar ausencia' }).click();
        const start = new Date();
        start.setDate(start.getDate() + 20);
        const end = new Date(start);
        end.setDate(end.getDate() + 2);
        await page.getByLabel('Tipo de ausencia').selectOption('vacation');
        await page.getByLabel('Desde').fill(start.toISOString().slice(0, 10));
        await page.getByLabel('Hasta').fill(end.toISOString().slice(0, 10));
        await page.getByLabel('Motivo y contexto').fill('Vacaciones E2E para validar el flujo completo de aprobación.');
        await page.getByRole('button', { name: 'Enviar solicitud' }).click();
        await expect(page.getByText('Solicitud enviada por 3 día(s).')).toBeVisible();
        await expect(page.getByText(/Vacaciones E2E para validar/)).toBeVisible();
        await logout(page);
    });

    test('RR. HH. aprueba, asigna onboarding y crea un objetivo', async ({ page }) => {
        await loginAsAdmin(page);
        await page.getByRole('navigation', { name: 'Navegación principal' }).getByRole('link', { name: 'Solicitudes' }).click();
        const leaveRow = page.getByRole('row').filter({ hasText: 'Vacaciones E2E para validar' });
        await leaveRow.getByRole('button', { name: 'Aprobar' }).click();
        await expect(page.getByText('Solicitud aprobada.')).toBeVisible();

        await page.getByRole('navigation', { name: 'Navegación principal' }).getByRole('link', { name: 'Onboarding' }).click();
        await page.getByRole('link', { name: 'Asignar tarea' }).click();
        await page.getByLabel('Persona').selectOption({ label: 'E2E Employee · Software Engineer' });
        await page.getByLabel('Tarea').fill('Firmar política de seguridad E2E');
        await page.getByLabel('Instrucciones').fill('Leer la política corporativa y marcar la tarea como completada.');
        await page.getByLabel('Prioridad').selectOption('high');
        await page.getByRole('button', { name: 'Asignar tarea' }).click();
        await expect(page.getByText('Tarea “Firmar política de seguridad E2E” asignada.')).toBeVisible();

        await page.getByRole('navigation', { name: 'Navegación principal' }).getByRole('link', { name: 'Objetivos' }).click();
        await page.getByRole('link', { name: 'Crear objetivo' }).click();
        await page.getByLabel('Persona').selectOption({ label: 'E2E Employee · Software Engineer' });
        await page.getByLabel('Resultado esperado').fill('Entregar mejora operativa E2E');
        await page.getByLabel('Cómo se medirá').fill('La mejora debe quedar validada y documentada en el equipo.');
        await page.getByRole('button', { name: 'Activar objetivo' }).click();
        await expect(page.getByText('Objetivo “Entregar mejora operativa E2E” activado.')).toBeVisible();
        await logout(page);
    });

    test('empleado entrega archivos, completa objetivos y confirma la aprobación', async ({ page }) => {
        await loginAsEmployee(page);
        await page.getByRole('navigation', { name: 'Navegación principal' }).getByRole('link', { name: 'Onboarding' }).click();
        const taskCard = page.getByRole('article').filter({ hasText: 'Firmar política de seguridad E2E' });
        await taskCard.getByRole('link', { name: /Abrir tarea y entregar/ }).click();
        await page.locator('input[type="file"]').setInputFiles(path.resolve('tests/e2e/fixtures/e2e-report.pdf'));
        await page.getByLabel('Mensaje para el revisor').fill('Adjunto el informe E2E con evidencia y conclusiones ejecutivas.');
        await page.getByRole('button', { name: 'Enviar a revisión' }).click();
        await expect(page.getByText('Entrega v1 enviada para revisión.')).toBeVisible();
        await expect(page.getByText('e2e-report.pdf')).toBeVisible();

        await page.getByRole('navigation', { name: 'Navegación principal' }).getByRole('link', { name: 'Objetivos' }).click();
        const goalCard = page.getByRole('article').filter({ hasText: 'Entregar mejora operativa E2E' });
        await goalCard.getByRole('spinbutton').fill('100');
        await goalCard.getByRole('button', { name: 'Guardar progreso' }).click();
        await expect(page.getByText('Progreso actualizado.')).toBeVisible();
        await expect(goalCard.getByRole('progressbar')).toHaveAttribute('value', '100');

        await page.getByRole('navigation', { name: 'Navegación principal' }).getByRole('link', { name: 'Solicitudes' }).click();
        await expect(page.getByRole('table').getByText('Aprobada')).toBeVisible();
        await logout(page);
    });

    test('RR. HH. revisa el archivo y aprueba formalmente la tarea', async ({ page }) => {
        await loginAsAdmin(page);
        await page.getByRole('navigation', { name: 'Navegación principal' }).getByRole('link', { name: 'Onboarding' }).click();
        const taskCard = page.getByRole('article').filter({ hasText: 'Firmar política de seguridad E2E' });
        await taskCard.getByRole('link', { name: /Revisar entregables/ }).click();
        await expect(page.getByRole('link', { name: /e2e-report.pdf/ })).toBeVisible();
        await page.getByLabel('Retroalimentación de la revisión').fill('El entregable contiene evidencia suficiente y cumple el estándar esperado.');
        await page.getByRole('button', { name: 'Aprobar entrega' }).click();
        await expect(page.getByText('Entrega aprobada y tarea completada.')).toBeVisible();
        await expect(page.getByText(/Aprobada por E2E Admin/)).toBeVisible();
    });
});

test('cerrar el navegador elimina la sesión administrativa', async ({ browser }) => {
    let context = await browser.newContext({ baseURL: 'http://127.0.0.1:8001' });
    let page = await context.newPage();
    await loginAsAdmin(page);

    const cookies = await context.cookies();
    const sessionCookie = cookies.find((cookie) => cookie.name === 'peopleos_secure_session_v2');
    expect(sessionCookie).toBeDefined();
    expect(sessionCookie?.expires).toBe(-1);
    expect(sessionCookie?.httpOnly).toBeTruthy();

    await context.close();

    context = await browser.newContext({ baseURL: 'http://127.0.0.1:8001' });
    page = await context.newPage();
    await page.goto('/admin/home');
    await expect(page).toHaveURL(/\/admin\/login$/);
    await context.close();
});

test('experiencia móvil sin desbordamiento y con movimiento reducido', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await loginAsEmployee(page);
    await expect(page.getByRole('button', { name: 'Abrir navegación' })).toBeVisible();
    const hasHorizontalOverflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth);
    expect(hasHorizontalOverflow).toBeFalsy();
    const animationName = await page.locator('.reveal-item').first().evaluate((element) => getComputedStyle(element).animationName);
    expect(animationName).toBe('none');
});

test('documento laboral privado se publica y firma con evidencia', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/documentos');
    await page.locator('#user_id').selectOption({ label: 'E2E Employee' });
    await page.locator('#title').fill('Política corporativa E2E');
    await page.locator('#category').selectOption('policy');
    await page.locator('#file').setInputFiles(path.resolve('tests/e2e/fixtures/e2e-report.pdf'));
    await page.getByLabel('Requiere firma electrónica del empleado').check();
    await page.getByRole('button', { name: 'Publicar en el expediente' }).click();
    await expect(page.getByText('Documento publicado de forma privada.')).toBeVisible();
    await logout(page);

    await loginAsEmployee(page);
    await page.goto('/documentos');
    const document = page.getByRole('article').filter({ hasText: 'Política corporativa E2E' });
    await expect(document.getByRole('link', { name: 'Descargar' })).toBeVisible();
    await document.getByText('Firmar', { exact: true }).click();
    await document.getByLabel('Nombre completo').fill('E2E Employee');
    await document.getByLabel(/He leído el documento/).check();
    await document.getByRole('button', { name: 'Confirmar firma' }).click();
    await expect(page.getByText('Firma electrónica registrada con sello de integridad y trazabilidad.')).toBeVisible();
});
