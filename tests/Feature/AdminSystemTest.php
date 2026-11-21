<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_library_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put('/settings', [
            'library_name' => 'Perpustakaan Uji',
            'library_address' => 'Jl. Uji No. 1',
            'library_info' => 'Informasi uji',
            'max_books' => 5,
            'loan_days' => 7,
            'fine_per_day' => 2500,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('settings', [
            'key' => 'library_name',
            'value' => 'Perpustakaan Uji',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'max_books',
            'value' => '5',
        ]);
    }

    public function test_admin_can_manage_petugas_accounts(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/accounts', [
            'name' => 'Petugas Uji',
            'email' => 'uji@libraria.local',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasNoErrors();

        $petugas = User::where('email', 'uji@libraria.local')->firstOrFail();
        $this->assertSame('petugas', $petugas->role);
        $this->assertTrue((bool) $petugas->is_active);

        $this->actingAs($admin)->put('/accounts/'.$petugas->id, [
            'name' => 'Petugas Uji Edit',
            'email' => 'uji@libraria.local',
            'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $petugas->id,
            'name' => 'Petugas Uji Edit',
            'role' => 'petugas',
        ]);

        $this->actingAs($admin)->post('/accounts/'.$petugas->id.'/toggle')
            ->assertRedirect();
        $this->assertFalse((bool) $petugas->fresh()->is_active);

        $this->actingAs($admin)->delete('/accounts/'.$petugas->id)
            ->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $petugas->id]);
    }
}
