<?php

namespace App\Livewire\Superuser;

use App\Actions\Jetstream\DeleteUser;
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

    public bool $verifyConfirmation = false;
    public int $verifyUserId = 0;

    public bool $deleteConfirmation = false;
    public int $deleteUserId = 0;

    public function render(): View
    {
        $query = User::query();

        return view('livewire.superuser.users', [
            'users' => $this->applyFilters($query)
                ->orderByDesc(User::FIELD_ID)
                ->paginate(self::PER_PAGE),
            ])
            ->title('Admin: Users');
    }

    private function applyFilters(Builder $query): Builder
    {
        if ($this->query) {
            $query->where(fn (Builder $query) => $query
                ->where(User::FIELD_NAME, 'like', '%' . $this->query . '%'))
                ->orWhere(User::FIELD_EMAIL, 'like', '%' . $this->query . '%');
        }

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
}
