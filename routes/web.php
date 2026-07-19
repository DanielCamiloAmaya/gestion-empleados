<?php

use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\AccountRecoveryController;
use App\Http\Controllers\AdminHomeController;
use App\Http\Controllers\ApprovalInboxController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ControlCenterAuthController;
use App\Http\Controllers\ControlCenterController;
use App\Http\Controllers\ControlCenterOperationsController;
use App\Http\Controllers\ControlCenterOrganizationController;
use App\Http\Controllers\ControlCenterUserController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeTransferController;
use App\Http\Controllers\ExpansionController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LeavePolicyController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LoginAdminController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutAdminController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\MfaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OffboardingController;
use App\Http\Controllers\OnboardingDeliverableController;
use App\Http\Controllers\OnboardingTaskController;
use App\Http\Controllers\OrganizationOwnerInvitationController;
use App\Http\Controllers\PerformanceGoalController;
use App\Http\Controllers\PerformanceReviewController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\TenantAdminLifecycleController;
use App\Http\Controllers\TenantSupportAccessController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth('admin')->check()) {
        return redirect()->route('admin.home');
    }

    if (auth()->check()) {
        return redirect()->route('home');
    }

    return redirect()->route('login');
});
Route::get('/health/ready', [HealthController::class, 'ready'])->name('health.ready');

Route::prefix('control-center')->name('control.')->group(function () {
    Route::get('/login', [ControlCenterAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [ControlCenterAuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/invitaciones/{token}', [ControlCenterAuthController::class, 'showInvitation'])->name('invitation.show');
    Route::post('/invitaciones/{token}', [ControlCenterAuthController::class, 'acceptInvitation'])->middleware('throttle:5,1')->name('invitation.accept');

    Route::middleware('platform')->group(function () {
        Route::get('/mfa/configurar', [ControlCenterAuthController::class, 'showEnrollment'])->name('mfa.enroll');
        Route::post('/mfa/configurar', [ControlCenterAuthController::class, 'enableMfa'])->middleware('throttle:5,1')->name('mfa.enable');
        Route::get('/mfa/verificar', [ControlCenterAuthController::class, 'showChallenge'])->name('mfa.challenge');
        Route::post('/mfa/verificar', [ControlCenterAuthController::class, 'verifyMfa'])->middleware('throttle:5,1')->name('mfa.verify');
        Route::post('/logout', [ControlCenterAuthController::class, 'logout'])->name('logout');
    });

    Route::middleware(['platform', 'platform.mfa'])->group(function () {
        Route::get('/', [ControlCenterController::class, 'dashboard'])->name('dashboard');
        Route::get('/auditoria', [ControlCenterController::class, 'audit'])->name('audit');
        Route::get('/empresas/nueva', [ControlCenterOrganizationController::class, 'create'])->middleware('platform:organizations.manage')->name('organizations.create');
        Route::post('/empresas', [ControlCenterOrganizationController::class, 'store'])->middleware('platform:organizations.manage')->name('organizations.store');
        Route::get('/empresas/{organization}', [ControlCenterOrganizationController::class, 'show'])->middleware('platform:organizations.view')->name('organizations.show');
        Route::patch('/empresas/{organization}/estado', [ControlCenterOrganizationController::class, 'transition'])->middleware('platform:organizations.manage')->name('organizations.transition');
        Route::post('/empresas/{organization}/entidades-legales', [ControlCenterOperationsController::class, 'legalEntity'])->middleware('platform:legal_entities.manage')->name('legal-entities.store');
        Route::patch('/entidades-legales/{legalEntity}/verificar', [ControlCenterOperationsController::class, 'verifyLegalEntity'])->middleware('platform:legal_entities.manage')->name('legal-entities.verify');
        Route::post('/empresas/{organization}/dominios', [ControlCenterOperationsController::class, 'domain'])->middleware('platform:domains.manage')->name('domains.store');
        Route::post('/dominios/{domain}/verificar', [ControlCenterOperationsController::class, 'verifyDomain'])->middleware('platform:domains.manage')->name('domains.verify');
        Route::put('/empresas/{organization}/suscripcion', [ControlCenterOperationsController::class, 'subscription'])->middleware('platform:subscriptions.manage')->name('subscriptions.update');
        Route::post('/empresas/{organization}/propietarios/invitar', [ControlCenterOperationsController::class, 'inviteOwner'])->middleware('platform:invitations.manage')->name('owner-invitations.store');
        Route::delete('/invitaciones-propietario/{invitation}', [ControlCenterOperationsController::class, 'revokeInvitation'])->middleware('platform:invitations.manage')->name('owner-invitations.revoke');
        Route::post('/empresas/{organization}/soporte', [ControlCenterOperationsController::class, 'supportGrant'])->middleware('platform:support.manage')->name('support.store');
        Route::delete('/soporte/{grant}', [ControlCenterOperationsController::class, 'revokeSupportGrant'])->middleware('platform:support.manage')->name('support.revoke');
        Route::get('/soporte/{grant}/sesion', [ControlCenterOperationsController::class, 'supportSession'])->middleware('platform:support.manage')->name('support.session');
        Route::get('/usuarios-internos', [ControlCenterUserController::class, 'index'])->middleware('platform:platform_users.manage')->name('users.index');
        Route::post('/usuarios-internos', [ControlCenterUserController::class, 'store'])->middleware('platform:platform_users.manage')->name('users.store');
        Route::delete('/usuarios-internos/{platformUser}', [ControlCenterUserController::class, 'disable'])->middleware('platform:platform_users.manage')->name('users.disable');
    });
});

Route::get('/activar-propietario/{token}', [OrganizationOwnerInvitationController::class, 'show'])->name('organization-owner-invitations.show');
Route::post('/activar-propietario/{token}', [OrganizationOwnerInvitationController::class, 'accept'])->middleware('throttle:5,1')->name('organization-owner-invitations.accept');
Route::get('/activar-propietario/{token}/completado', [OrganizationOwnerInvitationController::class, 'complete'])->name('organization-owner-invitations.complete');

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1');
Route::get('/admin/login', [LoginAdminController::class, 'showAdmin'])->name('admin.login');
Route::post('/admin/login', [LoginAdminController::class, 'loginAdmin'])->middleware('throttle:6,1');
Route::get('/recuperar/{actor}', [AccountRecoveryController::class, 'requestForm'])->name('recovery.request');
Route::post('/recuperar/{actor}', [AccountRecoveryController::class, 'send'])->middleware('throttle:3,10')->name('recovery.send');
Route::get('/recuperar/{actor}/{token}', [AccountRecoveryController::class, 'resetForm'])->name('recovery.reset');
Route::post('/recuperar/{actor}/{token}', [AccountRecoveryController::class, 'reset'])->middleware('throttle:5,10')->name('recovery.update');
Route::get('/activar-administrador/{token}', [TenantAdminLifecycleController::class, 'show'])->name('admin-invitations.show');
Route::post('/activar-administrador/{token}', [TenantAdminLifecycleController::class, 'accept'])->middleware('throttle:5,10')->name('admin-invitations.accept');
Route::get('/invitaciones/{token}', [InvitationController::class, 'show'])->name('invitations.accept');
Route::post('/invitaciones/{token}', [InvitationController::class, 'accept'])->middleware('throttle:6,1')->name('invitations.store');
Route::get('/sso/{connection}/redirect', [SsoController::class, 'redirect'])->middleware('plan:sso')->name('sso.redirect');
Route::get('/sso/{connection}/callback', [SsoController::class, 'callback'])->middleware(['throttle:10,1', 'plan:sso'])->name('sso.callback');

Route::middleware('authenticated.any')->group(function () {
    Route::get('/seguridad/mfa/verificar', [MfaController::class, 'challenge'])->name('mfa.challenge');
    Route::post('/seguridad/mfa/verificar', [MfaController::class, 'verify'])->middleware('throttle:6,1')->name('mfa.verify');
});

Route::middleware(['auth', 'mfa'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');
    Route::get('/solicitudes/nueva', [LeaveRequestController::class, 'create'])->middleware('plan:leave')->name('leave.create');
    Route::post('/solicitudes', [LeaveRequestController::class, 'store'])->middleware('plan:leave')->name('leave.store');
});

Route::middleware(['authenticated.any', 'mfa'])->group(function () {
    Route::get('/seguridad/mfa', [MfaController::class, 'settings'])->name('mfa.settings');
    Route::post('/seguridad/mfa/activar', [MfaController::class, 'enable'])->middleware('throttle:6,1')->name('mfa.enable');
    Route::delete('/seguridad/mfa', [MfaController::class, 'disable'])->middleware('throttle:6,1')->name('mfa.disable');
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notificaciones/leer-todas', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notificaciones/{notification}', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('/aprobaciones', [ApprovalInboxController::class, 'index'])->name('approvals.index');
    Route::patch('/aprobaciones/ausencias/{leaveRequest}', [LeaveRequestController::class, 'review'])->name('approvals.leave.review');
    Route::get('/documentos', [EmployeeDocumentController::class, 'index'])->middleware('plan:documents')->name('documents.index');
    Route::get('/documentos/{document}/descargar', [EmployeeDocumentController::class, 'download'])->middleware('plan:documents')->name('documents.download');
    Route::post('/documentos/{document}/firmar', [EmployeeDocumentController::class, 'sign'])->middleware('plan:documents')->name('documents.sign');
    Route::get('/evaluaciones', [PerformanceReviewController::class, 'index'])->middleware('plan:reviews')->name('reviews.index');
    Route::get('/talento-plus', [ExpansionController::class, 'index'])->middleware('plan:talent')->name('expansion.index');
    Route::post('/talento-plus/asistencia', [ExpansionController::class, 'clock'])->middleware('plan:talent')->name('attendance.clock');
    Route::post('/talento-plus/formacion/{enrollment}/completar', [ExpansionController::class, 'complete'])->middleware('plan:talent')->name('courses.complete');
    Route::post('/evaluaciones/{review}/reconocer', [PerformanceReviewController::class, 'acknowledge'])->middleware('plan:reviews')->name('reviews.acknowledge');
    Route::get('/empleados', [EmpleadoController::class, 'index'])->name('empleados.index');
    Route::get('/empleados/{empleado}', [EmpleadoController::class, 'show'])->name('empleados.show');
    Route::get('/departamentos', [DepartamentoController::class, 'index'])->name('departamentos.index');
    Route::get('/solicitudes', [LeaveRequestController::class, 'index'])->middleware('plan:leave')->name('leave.index');
    Route::get('/onboarding', [OnboardingTaskController::class, 'index'])->middleware('plan:onboarding')->name('onboarding.index');
    Route::get('/onboarding/{task}', [OnboardingTaskController::class, 'show'])->middleware('plan:onboarding')->name('onboarding.show');
    Route::post('/onboarding/{task}/entregables', [OnboardingDeliverableController::class, 'store'])->middleware('plan:onboarding')->name('deliverables.store');
    Route::patch('/entregables/{submission}/revision', [OnboardingDeliverableController::class, 'review'])->middleware('plan:onboarding')->name('deliverables.review');
    Route::get('/archivos-entregables/{deliverableFile}/descargar', [OnboardingDeliverableController::class, 'download'])->middleware('plan:onboarding')->name('deliverables.download');
    Route::patch('/onboarding/{task}/estado', [OnboardingTaskController::class, 'updateStatus'])->middleware('plan:onboarding')->name('onboarding.status');
    Route::get('/objetivos', [PerformanceGoalController::class, 'index'])->name('goals.index');
    Route::patch('/objetivos/{goal}/progreso', [PerformanceGoalController::class, 'updateProgress'])->name('goals.progress');
});

Route::prefix('admin')->middleware(['admin', 'mfa'])->group(function () {
    Route::get('/home', [AdminHomeController::class, 'index'])->name('admin.home');
    Route::post('/logout', [LogoutAdminController::class, 'logoutAdmin'])->name('admin.logout');
    Route::get('/auditoria', [AuditLogController::class, 'index'])->middleware('permission:audit.view')->name('audit.index');
    Route::get('/accesos', [AccessControlController::class, 'index'])->middleware('permission:security.manage')->name('access.index');
    Route::get('/accesos/soporte', [TenantSupportAccessController::class, 'index'])->middleware('permission:security.manage')->name('support-access.index');
    Route::patch('/accesos/soporte/{grant}', [TenantSupportAccessController::class, 'review'])->middleware('permission:security.manage')->name('support-access.review');
    Route::delete('/accesos/soporte/{grant}', [TenantSupportAccessController::class, 'revoke'])->middleware('permission:security.manage')->name('support-access.revoke');
    Route::post('/accesos/roles', [AccessControlController::class, 'role'])->middleware('permission:security.manage')->name('access.roles.store');
    Route::put('/accesos/politica-mfa', [AccessControlController::class, 'mfaPolicy'])->middleware('permission:security.manage')->name('access.mfa-policy');
    Route::put('/accesos/administradores/{admin}', [AccessControlController::class, 'assign'])->middleware('permission:security.manage')->name('access.assign');
    Route::post('/accesos/administradores/invitar', [TenantAdminLifecycleController::class, 'invite'])->middleware('permission:security.manage')->name('access.admins.invite');
    Route::delete('/accesos/administradores/{admin}', [TenantAdminLifecycleController::class, 'disable'])->middleware('permission:security.manage')->name('access.admins.disable');
    Route::patch('/accesos/administradores/{admin}/reactivar', [TenantAdminLifecycleController::class, 'enable'])->middleware('permission:security.manage')->name('access.admins.enable');
    Route::post('/documentos', [EmployeeDocumentController::class, 'store'])->middleware(['permission:documents.manage', 'plan:documents'])->name('documents.store');
    Route::get('/ausencias/configuracion', [LeavePolicyController::class, 'index'])->middleware('permission:approvals.review')->name('leave.settings');
    Route::post('/ausencias/politicas', [LeavePolicyController::class, 'policy'])->middleware('permission:approvals.review')->name('leave.policies.store');
    Route::post('/ausencias/saldos', [LeavePolicyController::class, 'balance'])->middleware('permission:approvals.review')->name('leave.balances.store');
    Route::post('/ausencias/festivos', [LeavePolicyController::class, 'holiday'])->middleware('permission:approvals.review')->name('leave.holidays.store');
    Route::post('/evaluaciones/ciclos', [PerformanceReviewController::class, 'cycle'])->middleware(['permission:reviews.manage', 'plan:reviews'])->name('reviews.cycles.store');
    Route::patch('/evaluaciones/{review}', [PerformanceReviewController::class, 'submit'])->middleware(['permission:reviews.manage', 'plan:reviews'])->name('reviews.submit');
    Route::get('/offboarding', [OffboardingController::class, 'index'])->middleware(['permission:offboarding.manage', 'plan:offboarding'])->name('offboarding.index');
    Route::post('/offboarding', [OffboardingController::class, 'store'])->middleware(['permission:offboarding.manage', 'plan:offboarding'])->name('offboarding.store');
    Route::patch('/offboarding/tareas/{task}', [OffboardingController::class, 'task'])->middleware(['permission:offboarding.manage', 'plan:offboarding'])->name('offboarding.tasks.update');
    Route::get('/reportes', [ReportController::class, 'index'])->middleware('permission:reports.view')->name('reports.index');
    Route::get('/plataforma', [PlatformController::class, 'index'])->middleware(['permission:integrations.manage', 'plan:api,sso,advanced_audit'])->name('platform.index');
    Route::post('/plataforma/tokens', [PlatformController::class, 'token'])->middleware(['permission:integrations.manage', 'plan:api'])->name('platform.tokens.store');
    Route::delete('/plataforma/tokens/{token}', [PlatformController::class, 'revoke'])->middleware(['permission:integrations.manage', 'plan:api'])->name('platform.tokens.revoke');
    Route::post('/plataforma/webhooks', [PlatformController::class, 'webhook'])->middleware(['permission:integrations.manage', 'plan:api'])->name('platform.webhooks.store');
    Route::post('/plataforma/integraciones/{integration}', [PlatformController::class, 'integration'])->middleware(['permission:integrations.manage', 'plan:api'])->name('platform.integrations.store');
    Route::post('/plataforma/sso', [PlatformController::class, 'sso'])->middleware(['permission:integrations.manage', 'plan:sso'])->name('platform.sso.store');
    Route::post('/plataforma/cumplimiento', [PlatformController::class, 'compliance'])->middleware(['permission:security.manage', 'plan:advanced_audit'])->name('platform.compliance.store');
    Route::patch('/plataforma/cumplimiento/{control}/verificar', [PlatformController::class, 'verifyCompliance'])->middleware(['permission:security.manage', 'plan:advanced_audit'])->name('platform.compliance.verify');
    Route::post('/talento-plus/vacantes', [ExpansionController::class, 'job'])->middleware(['permission:talent.manage', 'plan:talent'])->name('jobs.store');
    Route::post('/talento-plus/candidatos', [ExpansionController::class, 'candidate'])->middleware(['permission:talent.manage', 'plan:talent'])->name('candidates.store');
    Route::patch('/talento-plus/candidatos/{candidate}', [ExpansionController::class, 'candidateStage'])->middleware(['permission:talent.manage', 'plan:talent'])->name('candidates.stage');
    Route::post('/talento-plus/cursos', [ExpansionController::class, 'course'])->middleware(['permission:talent.manage', 'plan:talent'])->name('courses.store');
    Route::post('/talento-plus/asignaciones', [ExpansionController::class, 'enroll'])->middleware(['permission:talent.manage', 'plan:talent'])->name('courses.enroll');
    Route::post('/talento-plus/compensacion', [ExpansionController::class, 'compensation'])->middleware(['permission:compensation.manage', 'plan:talent'])->name('compensation.store');
    Route::patch('/solicitudes/{leaveRequest}/revision', [LeaveRequestController::class, 'review'])->middleware('permission:approvals.review')->name('leave.review');
    Route::get('/onboarding/nueva', [OnboardingTaskController::class, 'create'])->name('onboarding.create');
    Route::post('/onboarding', [OnboardingTaskController::class, 'store'])->middleware('permission:onboarding.manage')->name('onboarding.store');
    Route::get('/objetivos/nuevo', [PerformanceGoalController::class, 'create'])->name('goals.create');
    Route::post('/objetivos', [PerformanceGoalController::class, 'store'])->middleware('permission:goals.manage')->name('goals.store');

    Route::get('/empleados/nuevo', [EmpleadoController::class, 'create'])->name('empleados.create');
    Route::get('/empleados/importar', [EmployeeTransferController::class, 'create'])->middleware('permission:people.manage')->name('employees.import.create');
    Route::post('/empleados/importar', [EmployeeTransferController::class, 'store'])->middleware('permission:people.manage')->name('employees.import.store');
    Route::get('/empleados/plantilla.csv', [EmployeeTransferController::class, 'template'])->middleware('permission:people.manage')->name('employees.import.template');
    Route::get('/empleados/exportar.csv', [EmployeeTransferController::class, 'export'])->middleware('permission:reports.view')->name('employees.export');
    Route::post('/empleados', [EmpleadoController::class, 'store'])->middleware('permission:people.manage')->name('empleados.store');
    Route::get('/empleados/{empleado}/editar', [EmpleadoController::class, 'edit'])->name('empleados.edit');
    Route::put('/empleados/{empleado}', [EmpleadoController::class, 'update'])->middleware('permission:people.manage')->name('empleados.update');
    Route::delete('/empleados/{empleado}', [EmpleadoController::class, 'destroy'])->middleware('permission:people.manage')->name('empleados.destroy');

    Route::get('/departamentos/nuevo', [DepartamentoController::class, 'create'])->name('departamentos.create');
    Route::post('/departamentos', [DepartamentoController::class, 'store'])->name('departamentos.store');
    Route::get('/departamentos/{departamento}/editar', [DepartamentoController::class, 'edit'])->name('departamentos.edit');
    Route::put('/departamentos/{departamento}', [DepartamentoController::class, 'update'])->name('departamentos.update');
    Route::delete('/departamentos/{departamento}', [DepartamentoController::class, 'destroy'])->name('departamentos.destroy');
});
