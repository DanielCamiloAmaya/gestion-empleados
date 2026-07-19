# PeopleOS Full Product Audit Test Plan

## Application Overview

PeopleOS exposes three security and experience boundaries: employee self-service, tenant administration, and the internal SaaS Control Center. This audit validates the product as a buyer would: core journeys, negative authorization, route health, data persistence, browser/session security, responsive behavior, console/network health, and clarity of the visible interface.

## Test Scenarios

### 1. Tenant administration

**Seed:** `tests/e2e/seed-admin.spec.ts`

#### 1.1. admin-navigation-health

**File:** `tests/e2e/audit/admin-navigation-health.spec.ts`

**Steps:**
  1. Sign in as a tenant owner.
    - expect: authentication finishes on the administration dashboard.
  2. Visit every owner-accessible administration surface.
    - expect: every page returns a successful response.
    - expect: every page has one visible primary heading.
    - expect: no page logs an uncaught browser error.
    - expect: no desktop or mobile page has horizontal overflow.
  3. Activate reduced-motion preferences.
    - expect: reveal animations are disabled.

#### 1.2. governance-and-configuration

**File:** `tests/e2e/audit/governance-and-configuration.spec.ts`

**Steps:**
  1. Create a leave policy, holiday, and employee balance.
    - expect: each configuration persists and is visible.
  2. Create a custom role with explicit permissions and assign it.
    - expect: the assignment persists.
  3. Create a compliance control and API token.
    - expect: the control is visible and the token is shown only once.
  4. Open reporting, audit, support-access, and integration surfaces.
    - expect: each surface renders without browser errors.

#### 1.3. talent-and-lifecycle

**File:** `tests/e2e/audit/talent-and-lifecycle.spec.ts`

**Steps:**
  1. Create a performance review cycle and publish a review.
    - expect: the employee receives a review in a submitted state.
  2. Open an offboarding case and complete its controls.
    - expect: the case closes only after all required tasks are complete.
  3. Create a job, candidate, course enrollment, and compensation record.
    - expect: each record persists in its module.

### 2. Employee self-service

**Seed:** `tests/e2e/seed-employee.spec.ts`

#### 2.1. employee-navigation-and-self-service

**File:** `tests/e2e/audit/employee-navigation-and-self-service.spec.ts`

**Steps:**
  1. Sign in as an employee.
    - expect: the employee dashboard is visible.
  2. Visit every employee-accessible surface.
    - expect: pages render successfully without console errors.
    - expect: administrative creation controls are absent.
  3. Create and inspect a leave request, clock entry, goal progress, notifications, and onboarding delivery.
    - expect: each employee action persists.
  4. Check mobile layout and keyboard focus.
    - expect: there is no horizontal overflow and focus is visible.

### 3. Control Center

**Seed:** `tests/e2e/seed-control-center.spec.ts`

#### 3.1. control-center-navigation-and-security

**File:** `tests/e2e/audit/control-center-navigation-and-security.spec.ts`

**Steps:**
  1. Sign in with password and TOTP.
    - expect: MFA is required before protected pages load.
  2. Visit portfolio, organization, internal users, and platform audit.
    - expect: each page renders successfully without console errors.
  3. Attempt tenant and platform routes from the wrong identity boundary.
    - expect: access is denied or redirected.
  4. Close the browser context.
    - expect: a new context has no authenticated session.

#### 3.2. company-lifecycle-and-support

**File:** `tests/e2e/audit/company-lifecycle-and-support.spec.ts`

**Steps:**
  1. Provision an organization and accept the first-owner invitation.
    - expect: the workspace remains in onboarding until all activation controls pass.
  2. Verify legal identity and register a domain.
    - expect: the activation gate accurately shows remaining controls.
  3. Request temporary support access.
    - expect: no support session is usable before customer approval.
  4. Approve the request as the customer and reopen it as the assigned specialist.
    - expect: only approved read-only metadata is exposed.
    - expect: both audit ledgers contain evidence.

### 4. Cross-cutting quality

**Seed:** `tests/e2e/seed-admin.spec.ts`

#### 4.1. browser-quality-matrix

**File:** `tests/e2e/audit/browser-quality-matrix.spec.ts`

**Steps:**
  1. Capture representative employee, admin, and Control Center pages at desktop and mobile sizes.
    - expect: layout, typography, and visual hierarchy remain coherent.
  2. Inspect links, images, forms, labels, landmarks, and headings.
    - expect: no broken internal links or missing required form labels.
  3. Inspect browser console and failed network requests.
    - expect: no uncaught errors, failed application assets, or 5xx responses.
  4. Inspect security headers and session cookies.
    - expect: sensitive cookies are HTTP-only, non-persistent, and use the configured same-site policy.
