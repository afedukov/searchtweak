<?php

namespace Tests\Feature\Livewire;

use App\Http\Middleware\UserOnline;
use App\Livewire\Superuser\Users;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function test_filter_role_super_admin(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
        ]);

        $regularUser = User::factory()->create([
            User::FIELD_SUPER_ADMIN => false,
            User::FIELD_NAME => 'Regular Joe',
        ]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->set('filterRole', 'super_admin')
            ->assertSee($admin->name)
            ->assertDontSee('Regular Joe');
    }

    public function test_filter_role_regular(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
            User::FIELD_NAME => 'Admin User',
        ]);

        $regularUser = User::factory()->create([
            User::FIELD_SUPER_ADMIN => false,
            User::FIELD_NAME => 'Regular Joe',
        ]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->set('filterRole', 'regular')
            ->assertSee('Regular Joe')
            ->assertDontSee('Admin User');
    }

    public function test_filter_role_all(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
            User::FIELD_NAME => 'Admin User',
        ]);

        $regularUser = User::factory()->create([
            User::FIELD_SUPER_ADMIN => false,
            User::FIELD_NAME => 'Regular Joe',
        ]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->set('filterRole', '')
            ->assertSee('Admin User')
            ->assertSee('Regular Joe');
    }

    public function test_filter_verified(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
            User::FIELD_EMAIL_VERIFIED_AT => now(),
            User::FIELD_NAME => 'Verified User',
        ]);

        $unverifiedUser = User::factory()->create([
            User::FIELD_EMAIL_VERIFIED_AT => null,
            User::FIELD_NAME => 'Unverified User',
        ]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->set('filterVerified', 'verified')
            ->assertSee('Verified User')
            ->assertDontSee('Unverified User');
    }

    public function test_filter_not_verified(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
            User::FIELD_EMAIL_VERIFIED_AT => now(),
            User::FIELD_NAME => 'Verified User',
        ]);

        $unverifiedUser = User::factory()->create([
            User::FIELD_EMAIL_VERIFIED_AT => null,
            User::FIELD_NAME => 'Unverified User',
        ]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->set('filterVerified', 'not_verified')
            ->assertSee('Unverified User')
            ->assertDontSee('Verified User');
    }

    public function test_filter_online(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
            User::FIELD_LAST_ACTIVE_AT => now(),
            User::FIELD_NAME => 'Online User',
        ]);

        $offlineUser = User::factory()->create([
            User::FIELD_LAST_ACTIVE_AT => now()->subMinutes(10),
            User::FIELD_NAME => 'Offline User',
        ]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->set('filterOnline', 'online')
            ->assertSee('Online User')
            ->assertDontSee('Offline User');
    }

    public function test_filter_offline(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
            User::FIELD_LAST_ACTIVE_AT => now(),
            User::FIELD_NAME => 'Online User',
        ]);

        $offlineUser = User::factory()->create([
            User::FIELD_LAST_ACTIVE_AT => now()->subMinutes(10),
            User::FIELD_NAME => 'Offline User',
        ]);

        $neverUser = User::factory()->create([
            User::FIELD_LAST_ACTIVE_AT => null,
            User::FIELD_NAME => 'Never User',
        ]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->set('filterOnline', 'offline')
            ->assertDontSee('Online User')
            ->assertSee('Offline User')
            ->assertSee('Never User');
    }

    public function test_combined_filters(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
            User::FIELD_EMAIL_VERIFIED_AT => now(),
            User::FIELD_LAST_ACTIVE_AT => now(),
            User::FIELD_NAME => 'Admin Online Verified',
        ]);

        $regularOnline = User::factory()->create([
            User::FIELD_SUPER_ADMIN => false,
            User::FIELD_EMAIL_VERIFIED_AT => now(),
            User::FIELD_LAST_ACTIVE_AT => now(),
            User::FIELD_NAME => 'Regular Online Verified',
        ]);

        $regularOffline = User::factory()->create([
            User::FIELD_SUPER_ADMIN => false,
            User::FIELD_EMAIL_VERIFIED_AT => null,
            User::FIELD_LAST_ACTIVE_AT => now()->subHour(),
            User::FIELD_NAME => 'Regular Offline Unverified',
        ]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->set('filterRole', 'regular')
            ->set('filterVerified', 'verified')
            ->set('filterOnline', 'online')
            ->assertSee('Regular Online Verified')
            ->assertDontSee('Admin Online Verified')
            ->assertDontSee('Regular Offline Unverified');
    }

    public function test_online_count_in_render_data(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
            User::FIELD_LAST_ACTIVE_AT => now(),
        ]);

        User::factory()->create([User::FIELD_LAST_ACTIVE_AT => now()]);
        User::factory()->create([User::FIELD_LAST_ACTIVE_AT => now()->subMinutes(10)]);
        User::factory()->create([User::FIELD_LAST_ACTIVE_AT => null]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->assertViewHas('onlineCount', 2);
    }

    public function test_middleware_updates_last_active_at(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            User::FIELD_LAST_ACTIVE_AT => null,
        ]);

        Auth::login($user);

        $middleware = new UserOnline();
        $request = Request::create('/test', 'GET');
        $request->setLaravelSession(app('session.store'));

        $middleware->handle($request, fn ($r) => $r);

        $user->refresh();
        $this->assertNotNull($user->last_active_at);
    }

    public function test_middleware_throttles_last_active_at_update(): void
    {
        $recentTime = now()->subMinutes(2);

        $user = User::factory()->withPersonalTeam()->create([
            User::FIELD_LAST_ACTIVE_AT => $recentTime,
        ]);

        Auth::login($user);

        $middleware = new UserOnline();
        $request = Request::create('/test', 'GET');
        $request->setLaravelSession(app('session.store'));

        $middleware->handle($request, fn ($r) => $r);

        $user->refresh();
        // Should NOT have been updated because less than 5 minutes passed
        $this->assertEquals(
            $recentTime->format('Y-m-d H:i:s'),
            $user->last_active_at->format('Y-m-d H:i:s')
        );
    }

    public function test_filter_resets_pagination(): void
    {
        $admin = User::factory()->withPersonalTeam()->create([
            User::FIELD_SUPER_ADMIN => true,
        ]);

        // Create enough users to have multiple pages
        User::factory()->count(15)->create();

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('gotoPage', 2)
            ->set('filterRole', 'regular')
            ->assertNotSet('paginators.page', 2);
    }
}
