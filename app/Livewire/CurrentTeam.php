<?php

namespace App\Livewire;

use App\Actions\Users\SyncUserTags;
use App\Livewire\Traits\Users\ManageTagsTrait;
use App\Models\Team;
use App\Models\User;
use App\Notifications\MessageNotification;
use App\Policies\Roles;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Laravel\Jetstream\RedirectsActions;
use Laravel\Jetstream\Role;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

/**
 * @property array $recipients
 */
class CurrentTeam extends TeamMemberManager
{
    use WithPagination;
    use RedirectsActions;
    use ManageTagsTrait;

    public const int PER_PAGE = 10;

    public const array DEFAULT_FILTER_ROLE = ['owner', Roles::ROLE_ADMIN['key'], Roles::ROLE_EVALUATOR['key']];

    public const string SESSION_FILTER_ROLE = 'team-filter-role';

    public bool $addTeamMemberModal = false;
    public bool $sendUserMessageModal = false;
    public bool $sendTeamMessageModal = false;
    public bool $editTeamModal = false;

    public bool $apiTokenModal = false;
    public ?string $apiTokenPlain = null;
    public ?PersonalAccessToken $apiToken = null;

    public ?User $selectedUser = null;

    public string $sendTeamMessageTo = '';

    public int $entityId = 0;

    public array $message = [
        'url' => '',
        'message' => '',
    ];

    public array $filterRole = self::DEFAULT_FILTER_ROLE;

    public int $filterTagId = 0;

    public function mount($team = null): void
    {
        parent::mount($team);

        if (Session::has(self::SESSION_FILTER_ROLE)) {
            $this->filterRole = Session::get(self::SESSION_FILTER_ROLE);
        }

        $this->initializeManageTags();

        $this->apiToken = $this->team->tokens()->first();
    }

    public function render(): View
    {
        Session::put(self::SESSION_FILTER_ROLE, $this->filterRole);

        $teamOwner = User::query()
            ->with('tags')
            ->whereKey($this->team->user_id)
            ->when($this->filterTagId, fn (Builder $query) =>
                $query->whereHas('tags', fn (Builder $query) => $query->whereKey($this->filterTagId))
            )
            ->select([
                'users.*',
                \DB::raw('null as pivot_team_id'),
                \DB::raw('null as pivot_user_id'),
                \DB::raw("'owner' as pivot_role"),
                \DB::raw('null as pivot_created_at'),
                \DB::raw('null as pivot_updated_at'),
            ]);

        return view('livewire.pages.current-team', [
            'filterRoles' => array_merge([['key' => 'owner', 'name' => 'Owner']], Roles::all()),
            'users' => $this->team
                ->users()
                ->with('tags', 'teams')
                ->when(in_array('owner', $this->filterRole), fn (Builder $query) => $query->union($teamOwner))
                ->when($this->filterTagId, fn (Builder $query) =>
                    $query->whereHas('tags', fn (Builder $query) => $query->whereKey($this->filterTagId))
                )
                ->whereHas('teams', function (Builder $query) {
                    $query->whereKey($this->team->id)
                        ->whereIn('role', $this->filterRole);
                })
                ->orderBy(
                    \DB::raw(sprintf('FIELD(pivot_role, "owner", "%s", "%s")', Roles::ROLE_ADMIN['key'], Roles::ROLE_EVALUATOR['key'])),
                )
                ->orderBy(User::FIELD_NAME)
                ->paginate(self::PER_PAGE),
        ])->title(sprintf('Team Members: %s', $this->team->name));
    }

    public function resetFilter(): void
    {
        $this->filterRole = self::DEFAULT_FILTER_ROLE;

        Session::forget(self::SESSION_FILTER_ROLE);
    }

    public function sendMessageToUser(User $user): void
    {
        $this->resetErrorBag();
        $this->selectedUser = $user;
        $this->sendUserMessageModal = true;
    }

    public function sendMessage(): void
    {
        try {
            Gate::authorize('sendMessage', $this->team);

            // strip html tags from message except for <b>, <i>
            $this->message['message'] = strip_tags($this->message['message'], '<b><i>');

            $data = [
                'message' => $this->message,
            ];

            $rules = [
                'message.url' => ['nullable', 'url'],
                'message.message' => ['required', 'string', 'max:255'],
            ];

            if ($this->selectedUser === null) {
                $data['recipient'] = $this->sendTeamMessageTo;
                $rules['recipient'] = ['required', 'string', Rule::in(array_merge(['all'], array_column($this->roles, 'key')))];
            }

            Validator::make(
                data: $data,
                rules: $rules,
                attributes: [
                    'message.url' => 'URL',
                    'message.message' => 'message',
                ])
                ->validate();

            if ($this->selectedUser) {
                $recipients = [$this->selectedUser];
            } elseif ($this->sendTeamMessageTo === 'all') {
                $recipients = $this->team->allUsers();
            } else {
                $recipients = $this->team
                    ->users()
                    ->wherePivot('role', $this->sendTeamMessageTo)
                    ->get();

                if ($this->sendTeamMessageTo === Roles::ROLE_ADMIN['key']) {
                    $recipients->push($this->team->owner);
                }

                $recipients = $recipients->unique();
            }

            Notification::send(
                $recipients,
                new MessageNotification(Auth::user(), $this->message['message'], $this->message['url'])
            );
        } catch (AuthorizationException $e) {
            Toaster::error($e->getMessage());
            $this->resetMessage();

            return;
        }

        $this->resetMessage();

        Toaster::success('Message sent successfully.');
    }

    private function resetMessage(): void
    {
        $this->sendUserMessageModal = false;
        $this->sendTeamMessageModal = false;
        $this->sendTeamMessageTo = '';
        $this->message = [
            'url' => '',
            'message' => '',
        ];
    }

    public function getRecipientsProperty(): array
    {
        return array_merge([[
                'key' => 'all',
                'name' => 'All',
                'description' => 'Send message to all team members',
                'total' => $this->team->allUsers()->count(),
            ]], array_map(function (Role $role) {
                $total = $this->team->users()->wherePivot('role', $role->key)->count();
                if ($role->key === Roles::ROLE_ADMIN['key']) {
                    $total++;
                }

                return [
                    'key' => $role->key,
                    'name' => $role->name,
                    'description' => sprintf('Send message to all %s', Str::plural($role->name)),
                    'total' => $total,
                ];
            }, $this->roles));
    }

    public function saveTeam(): void
    {
        try {
            Gate::authorize('update', $this->team);
        } catch (AuthorizationException $e) {
            Toaster::error($e->getMessage());
            $this->editTeamModal = false;

            return;
        }

        $this->validate([
            'teamName' => ['required', 'string', 'max:255'],
        ]);

        $this->team->name = $this->teamName;

        $dirty = $this->team->isDirty(Team::FIELD_NAME);

        $this->team->save();

        $this->editTeamModal = false;

        if ($dirty) {
            Toaster::success('Team name updated successfully.');
        }
    }

    public function saveUserTags(SyncUserTags $action): void
    {
        $this->error = '';

        try {
            $user = User::find($this->entityId);
            if (!$user) {
                throw new \Exception('User not found');
            }

            $action->sync($user, $this->team, $this->tags ?? []);
        } catch (\Exception $e) {
            Toaster::error($e->getMessage());
        }
    }

    public function deleteApiToken(): void
    {
        try {
            Gate::authorize('apiToken', $this->team);
        } catch (AuthorizationException $e) {
            Toaster::error($e->getMessage());

            return;
        }

        $this->team->tokens()->delete();

        $this->apiToken = null;
        $this->apiTokenPlain = null;
    }

    public function generateNewApiToken(): void
    {
        try {
            Gate::authorize('apiToken', $this->team);
        } catch (AuthorizationException $e) {
            Toaster::error($e->getMessage());

            return;
        }

        $this->team->tokens()->delete();

        $token = $this->team->createToken('API Token')->plainTextToken;
        $pureToken = explode('|', $token)[1] ?? null;

        $this->apiTokenPlain = $pureToken ?? $token;

        $this->apiToken = $this->team->tokens()->first();
    }
}
