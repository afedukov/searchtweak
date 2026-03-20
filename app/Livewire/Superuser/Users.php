<?php

namespace App\Livewire\Superuser;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Jetstream\DeleteUser;
use App\Http\Middleware\UserOnline;
use App\Livewire\Forms\UserForm;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Toaster;

class Users extends Component
{
    use WithPagination;

    public const int PER_PAGE = 10;

    public string $query = '';

    public string $filterRole = '';
    public string $filterVerified = '';
    public string $filterOnline = '';

    public bool $verifyConfirmation = false;
    public int $verifyUserId = 0;

    public bool $deleteConfirmation = false;
    public int $deleteUserId = 0;

    public bool $superAdminConfirmation = false;
    public int $superAdminUserId = 0;

    public UserForm $userForm;
    public bool $createUserModal = false;

    public function updated($property): void
    {
        if (in_array($property, ['query', 'filterRole', 'filterVerified', 'filterOnline'])) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        $onlineThreshold = now()->subMinutes(UserOnline::CACHE_MINUTES);

        $query = User::query();

        return view('livewire.superuser.users', [
            'users' => $this->applyFilters($query, $onlineThreshold)
                ->orderByDesc(User::FIELD_ID)
                ->paginate(self::PER_PAGE),
            'onlineCount' => User::where(User::FIELD_LAST_ACTIVE_AT, '>', $onlineThreshold)->count(),
            ])
            ->title('Admin: Users');
    }

    private function applyFilters(Builder $query, \Carbon\Carbon $onlineThreshold): Builder
    {
        if ($this->query) {
            $query->where(fn (Builder $q) => $q
                ->where(User::FIELD_NAME, 'like', '%' . $this->query . '%')
                ->orWhere(User::FIELD_EMAIL, 'like', '%' . $this->query . '%'));
        }

        $query->when($this->filterRole === 'super_admin', fn (Builder $q) => $q->where(User::FIELD_SUPER_ADMIN, true))
            ->when($this->filterRole === 'regular', fn (Builder $q) => $q->where(User::FIELD_SUPER_ADMIN, false));

        $query->when($this->filterVerified === 'verified', fn (Builder $q) => $q->whereNotNull(User::FIELD_EMAIL_VERIFIED_AT))
            ->when($this->filterVerified === 'not_verified', fn (Builder $q) => $q->whereNull(User::FIELD_EMAIL_VERIFIED_AT));

        $query->when($this->filterOnline === 'online', fn (Builder $q) => $q->where(User::FIELD_LAST_ACTIVE_AT, '>', $onlineThreshold))
            ->when($this->filterOnline === 'offline', fn (Builder $q) => $q->where(fn (Builder $q2) => $q2
                ->whereNull(User::FIELD_LAST_ACTIVE_AT)
                ->orWhere(User::FIELD_LAST_ACTIVE_AT, '<=', $onlineThreshold)));

        return $query;
    }

    public function verifyEmail(): void
    {
        try {
            Gate::authorize('superuser', Auth::user());

            $user = User::findOrFail($this->verifyUserId);

            if ($user->hasVerifiedEmail()) {
                throw new \Exception('User email already verified.');
            }

            $user->markEmailAsVerified();

            Toaster::info('User email verified.');
        } catch (\Throwable $e) {
            Toaster::error($e->getMessage());
        } finally {
            $this->verifyConfirmation = false;
            $this->verifyUserId = 0;
        }
    }

    public function deleteUser(DeleteUser $action): void
    {
        try {
            Gate::authorize('superuser', Auth::user());

            $user = User::findOrFail($this->deleteUserId);

            $action->delete($user);

            Toaster::info('User deleted.');
        } catch (\Throwable $e) {
            Toaster::error($e->getMessage());
        } finally {
            $this->deleteConfirmation = false;
            $this->deleteUserId = 0;
        }
    }

    public function createUser(): void
    {
        $this->userForm->reset();
        $this->userForm->resetErrorBag();
        $this->createUserModal = true;
    }

    public function saveUser(CreateNewUser $action): void
    {
        Gate::authorize('superuser', Auth::user());

        $this->userForm->validate();

        try {
            $user = $action->create([
                'name' => $this->userForm->name,
                'email' => $this->userForm->email,
                'password' => $this->userForm->password,
                'password_confirmation' => $this->userForm->password,
            ]);

            $user->markEmailAsVerified();

            $this->createUserModal = false;

            Toaster::info('User created.');
        } catch (\Throwable $e) {
            Toaster::error($e->getMessage());
        }
    }

    public function toggleSuperAdmin(): void
    {
        try {
            Gate::authorize('superuser', Auth::user());

            $user = User::findOrFail($this->superAdminUserId);

            if ($user->id === Auth::id()) {
                throw new \Exception('You cannot change your own super admin status.');
            }

            $user->super_admin = !$user->super_admin;
            $user->save();

            Toaster::info($user->super_admin ? 'Super admin granted.' : 'Super admin revoked.');
        } catch (\Throwable $e) {
            Toaster::error($e->getMessage());
        } finally {
            $this->superAdminConfirmation = false;
            $this->superAdminUserId = 0;
        }
    }
}
