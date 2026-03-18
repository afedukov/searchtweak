<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Superuser\Users;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperuserUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_grant_super_admin_to_another_user(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
        ]);

        $target = User::factory()->create([
            User::FIELD_SUPER_ADMIN => false,
        ]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->set('superAdminUserId', $target->id)
            ->call('toggleSuperAdmin')
            ->assertSet('superAdminConfirmation', false)
            ->assertSet('superAdminUserId', 0);

        $this->assertTrue($target->fresh()->super_admin);
    }

    public function test_super_admin_can_revoke_super_admin_from_another_user(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
        ]);

        $target = User::factory()->create([
            User::FIELD_SUPER_ADMIN => true,
        ]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->set('superAdminUserId', $target->id)
            ->call('toggleSuperAdmin')
            ->assertSet('superAdminConfirmation', false)
            ->assertSet('superAdminUserId', 0);

        $this->assertFalse($target->fresh()->super_admin);
    }

    public function test_super_admin_cannot_toggle_own_super_admin_status(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
        ]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->set('superAdminUserId', $admin->id)
            ->call('toggleSuperAdmin')
            ->assertSet('superAdminConfirmation', false)
            ->assertSet('superAdminUserId', 0);

        // Should remain unchanged
        $this->assertTrue($admin->fresh()->super_admin);
    }

    public function test_non_super_admin_cannot_toggle_super_admin(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => false,
        ]);

        $target = User::factory()->create([
            User::FIELD_SUPER_ADMIN => false,
        ]);

        Livewire::actingAs($user)
            ->test(Users::class)
            ->set('superAdminUserId', $target->id)
            ->call('toggleSuperAdmin');

        $this->assertFalse($target->fresh()->super_admin);
    }

    public function test_super_admin_can_create_user(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
        ]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('createUser')
            ->assertSet('createUserModal', true)
            ->set('userForm.name', 'New User')
            ->set('userForm.email', 'newuser@example.com')
            ->set('userForm.password', 'Password1')
            ->call('saveUser')
            ->assertSet('createUserModal', false);

        $user = User::where('email', 'newuser@example.com')->first();

        $this->assertNotNull($user);
        $this->assertEquals('New User', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->currentTeam);
    }

    public function test_create_user_validates_required_fields(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
        ]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('createUser')
            ->set('userForm.name', '')
            ->set('userForm.email', '')
            ->set('userForm.password', '')
            ->call('saveUser')
            ->assertHasErrors(['userForm.name', 'userForm.email', 'userForm.password']);
    }

    public function test_create_user_validates_unique_email(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
        ]);

        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('createUser')
            ->set('userForm.name', 'Test')
            ->set('userForm.email', 'taken@example.com')
            ->set('userForm.password', 'Password1')
            ->call('saveUser')
            ->assertHasErrors(['userForm.email']);
    }

    public function test_create_user_validates_weak_password(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
        ]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('createUser')
            ->set('userForm.name', 'Test')
            ->set('userForm.email', 'test@example.com')
            ->set('userForm.password', 'short')
            ->call('saveUser')
            ->assertHasErrors(['userForm.password']);
    }

    public function test_non_super_admin_cannot_create_user(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => false,
        ]);

        Livewire::actingAs($user)
            ->test(Users::class)
            ->set('userForm.name', 'New User')
            ->set('userForm.email', 'newuser@example.com')
            ->set('userForm.password', 'Password1')
            ->call('saveUser');

        $this->assertNull(User::where('email', 'newuser@example.com')->first());
    }
}
