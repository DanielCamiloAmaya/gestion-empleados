<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_never_creates_a_persistent_remember_cookie(): void
    {
        $admin = $this->admin();

        $response = $this->post(route('admin.login'), [
            'name' => $admin->email,
            'password' => 'SecurePassword1!',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('admin.home'))
            ->assertCookieMissing(Auth::guard('admin')->getRecallerName())
            ->assertSessionHas('security.guard', 'admin')
            ->assertSessionHas('security.version', 2);

        $sessionCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($sessionCookie);
        $this->assertSame(0, $sessionCookie->getExpiresTime(), 'La cookie debe expirar al cerrar el navegador.');
        $this->assertNull($admin->fresh()->remember_token);
    }

    public function test_admin_session_expires_after_fifteen_minutes_of_inactivity(): void
    {
        $admin = $this->admin();
        $this->post(route('admin.login'), ['name' => $admin->email, 'password' => 'SecurePassword1!']);

        $this->travel(15)->minutes();

        $this->get(route('admin.home'))
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('error', 'La sesión expiró por inactividad. Inicia sesión nuevamente.');
        $this->assertGuest('admin');
    }

    public function test_admin_login_url_always_shows_reauthentication_form(): void
    {
        $admin = $this->admin();
        $this->post(route('admin.login'), ['name' => $admin->email, 'password' => 'SecurePassword1!']);

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Gestiona el talento')
            ->assertDontSee('Resumen de personas');
    }

    public function test_employee_session_expires_after_thirty_minutes_of_inactivity(): void
    {
        $employee = User::factory()->create(['username' => 'session.employee', 'password' => 'SecurePassword1!']);
        $this->post(route('login'), ['username' => $employee->username, 'password' => 'SecurePassword1!']);

        $this->travel(30)->minutes();

        $this->get(route('home'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'La sesión expiró por inactividad. Inicia sesión nuevamente.');
        $this->assertGuest();
    }

    public function test_activity_renews_idle_window_without_extending_absolute_limit(): void
    {
        $admin = $this->admin();
        $this->post(route('admin.login'), ['name' => $admin->email, 'password' => 'SecurePassword1!']);

        $this->travel(10)->minutes();
        $this->get(route('admin.home'))->assertOk();
        $this->travel(10)->minutes();
        $this->get(route('admin.home'))->assertOk();
    }

    public function test_admin_session_has_an_eight_hour_absolute_limit(): void
    {
        $admin = $this->admin();
        $this->post(route('admin.login'), ['name' => $admin->email, 'password' => 'SecurePassword1!']);

        $this->travel(481)->minutes();
        $this->withSession(['security.last_activity_at' => now()->timestamp])
            ->get(route('admin.home'))
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('error', 'La sesión alcanzó su duración máxima. Inicia sesión nuevamente.');
        $this->assertGuest('admin');
    }

    public function test_authenticated_pages_are_not_browser_cacheable(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.home'))
            ->assertOk()
            ->assertHeader('Pragma', 'no-cache');

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
    }

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'Security Admin',
            'email' => 'security@example.com',
            'password' => 'SecurePassword1!',
            'role' => 'hr_admin',
        ]);
    }
}
