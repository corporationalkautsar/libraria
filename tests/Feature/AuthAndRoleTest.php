<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk ke Sistem');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'petugas@test.local',
            'password' => bcrypt('rahasia123'),
            'role' => 'petugas',
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'salah@test.local', 'password' => bcrypt('benar')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_petugas_cannot_access_admin_pages(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);

        foreach (['/books/create', '/settings', '/accounts', '/logs', '/reports', '/backup', '/monitoring'] as $url) {
            $this->actingAs($petugas)->get($url)->assertForbidden();
        }
    }

    public function test_admin_cannot_perform_petugas_operations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['/members/create', '/loans/create', '/returns/create'] as $url) {
            $this->actingAs($admin)->get($url)->assertForbidden();
        }
    }

    public function test_inactive_user_is_blocked(): void
    {
        $user = User::factory()->create(['role' => 'petugas', 'is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHas('error');
    }
}
