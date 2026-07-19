import { expect, test, type Page } from '@playwright/test';
import {
    collectBrowserEvidence,
    loginAsOwner,
    loginToControlCenter,
} from './helpers';

type VisualMetrics = {
    path: string;
    viewport: { width: number; height: number };
    horizontalOverflow: boolean;
    unnamedInteractiveElements: number;
    visibleTextBelow12px: number;
    visibleTextBelow14px: number;
    smallInteractiveTargets: number;
    headings: string[];
};

async function inspectVisualQuality(page: Page, path: string): Promise<VisualMetrics> {
    await page.goto(path);
    await page.waitForTimeout(900);
    return page.evaluate((currentPath) => {
        const visible = (element: Element) => {
            const style = getComputedStyle(element);
            const rect = element.getBoundingClientRect();
            return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
        };
        const textElements = [...document.querySelectorAll('p,span,small,strong,label,td,th,dt,dd,a,button')].filter(visible);
        const interactive = [...document.querySelectorAll('a,button,input:not([type="hidden"]),select,textarea')].filter(visible);
        const hasName = (element: Element) => {
            const html = element as HTMLElement;
            const input = element as HTMLInputElement;
            const idLabel = input.id ? document.querySelector(`label[for="${CSS.escape(input.id)}"]`) : null;
            return Boolean(
                html.innerText?.trim()
                || input.value?.trim()
                || element.getAttribute('aria-label')?.trim()
                || element.getAttribute('aria-labelledby')?.trim()
                || element.getAttribute('title')?.trim()
                || idLabel?.textContent?.trim()
                || element.closest('label')?.textContent?.trim()
            );
        };
        const fontSizes = textElements.map((element) => Number.parseFloat(getComputedStyle(element).fontSize));
        return {
            path: currentPath,
            viewport: { width: window.innerWidth, height: window.innerHeight },
            horizontalOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
            unnamedInteractiveElements: interactive.filter((element) => !hasName(element)).length,
            visibleTextBelow12px: fontSizes.filter((size) => size < 12).length,
            visibleTextBelow14px: fontSizes.filter((size) => size < 14).length,
            smallInteractiveTargets: interactive.filter((element) => {
                const rect = element.getBoundingClientRect();
                return rect.width < 24 || rect.height < 24;
            }).length,
            headings: [...document.querySelectorAll('h1,h2,h3')].filter(visible).map((heading) => `${heading.tagName}:${heading.textContent?.trim()}`),
        };
    }, path);
}

test('browser-quality-matrix', async ({ browser }, testInfo) => {
    const findings: Array<{ surface: string; metrics: VisualMetrics; evidence: ReturnType<typeof collectBrowserEvidence> }> = [];

    const adminContext = await browser.newContext({ baseURL: 'http://127.0.0.1:8001', viewport: { width: 1440, height: 900 } });
    const adminPage = await adminContext.newPage();
    const adminEvidence = collectBrowserEvidence(adminPage);
    await loginAsOwner(adminPage);
    const adminDashboard = await inspectVisualQuality(adminPage, '/admin/home');
    await adminPage.screenshot({ path: testInfo.outputPath('admin-dashboard-desktop.png'), fullPage: true });
    const adminTalent = await inspectVisualQuality(adminPage, '/talento-plus');
    await adminPage.screenshot({ path: testInfo.outputPath('talent-plus-desktop.png'), fullPage: true });
    findings.push({ surface: 'admin-dashboard', metrics: adminDashboard, evidence: adminEvidence });
    findings.push({ surface: 'talent-plus', metrics: adminTalent, evidence: adminEvidence });

    await adminPage.setViewportSize({ width: 390, height: 844 });
    await adminPage.emulateMedia({ reducedMotion: 'reduce' });
    const adminMobile = await inspectVisualQuality(adminPage, '/admin/home');
    await adminPage.screenshot({ path: testInfo.outputPath('admin-dashboard-mobile.png'), fullPage: true });
    findings.push({ surface: 'admin-mobile', metrics: adminMobile, evidence: adminEvidence });
    await adminContext.close();

    const controlContext = await browser.newContext({ baseURL: 'http://127.0.0.1:8001', viewport: { width: 1440, height: 900 } });
    const controlPage = await controlContext.newPage();
    const controlEvidence = collectBrowserEvidence(controlPage);
    await loginToControlCenter(controlPage);
    const controlDashboard = await inspectVisualQuality(controlPage, '/control-center');
    await controlPage.screenshot({ path: testInfo.outputPath('control-center-desktop.png'), fullPage: true });
    findings.push({ surface: 'control-center', metrics: controlDashboard, evidence: controlEvidence });
    await controlContext.close();

    await testInfo.attach('visual-quality-metrics.json', {
        body: Buffer.from(JSON.stringify(findings, null, 2)),
        contentType: 'application/json',
    });
    console.log(`AUDIT_VISUAL_METRICS=${JSON.stringify(findings)}`);

    expect(findings.every(({ metrics }) => !metrics.horizontalOverflow), 'no representative page should overflow horizontally').toBeTruthy();
    expect(findings.every(({ metrics }) => metrics.unnamedInteractiveElements === 0), 'all representative controls should have an accessible name').toBeTruthy();
});
