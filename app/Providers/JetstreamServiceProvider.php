<?php

namespace App\Providers;

use App\Actions\Jetstream\AddTeamMember;
use App\Actions\Jetstream\CreateTeam;
use App\Actions\Jetstream\DeleteTeam;
use App\Actions\Jetstream\DeleteUser;
use App\Actions\Jetstream\InviteTeamMember;
use App\Actions\Jetstream\RemoveTeamMember;
use App\Actions\Jetstream\UpdateTeamName;
use App\Livewire\DeleteTeamForm;
use App\Livewire\TeamMemberManager;
use App\Livewire\UpdateProfileInformationForm;
use App\Livewire\Users\DeleteUserForm;
use App\Models\User;
use App\Policies\Roles;
use App\Policies\SearchEndpointPolicy;
use App\Policies\SearchEvaluationPolicy;
use App\Policies\SearchModelPolicy;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Jetstream;

class JetstreamServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Jetstream::ignoreRoutes();

        $loader = AliasLoader::getInstance();
        $loader->alias('Laravel\Jetstream\Http\Livewire\DeleteTeamForm', DeleteTeamForm::class);
        $loader->alias('Laravel\Jetstream\Http\Livewire\DeleteUserForm', DeleteUserForm::class);
        $loader->alias('Laravel\Jetstream\Http\Livewire\TeamMemberManager', TeamMemberManager::class);
        $loader->alias('Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm', UpdateProfileInformationForm::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePermissions();
        $this->defineGates();

        Jetstream::createTeamsUsing(CreateTeam::class);
        Jetstream::updateTeamNamesUsing(UpdateTeamName::class);
        Jetstream::addTeamMembersUsing(AddTeamMember::class);
        Jetstream::inviteTeamMembersUsing(InviteTeamMember::class);
        Jetstream::removeTeamMembersUsing(RemoveTeamMember::class);
        Jetstream::deleteTeamsUsing(DeleteTeam::class);
        Jetstream::deleteUsersUsing(DeleteUser::class);
    }

    /**
     * Configure the roles and permissions that are available within the application.
     */
    protected function configurePermissions(): void
    {
        Jetstream::defaultApiTokenPermissions(['read']);

        foreach (Roles::all() as $role) {
            Jetstream::role($role['key'], $role['name'], $role['permissions'])
                ->description($role['description']);

        }
    }

    protected function defineGates(): void
    {
        Gate::define('create-endpoint', [SearchEndpointPolicy::class, 'create']);
        Gate::define('create-model', [SearchModelPolicy::class, 'create']);
        Gate::define('create-evaluation', [SearchEvaluationPolicy::class, 'create']);

        Gate::define('view-endpoints', [SearchEndpointPolicy::class, 'viewAny']);
        Gate::define('view-models', [SearchModelPolicy::class, 'viewAny']);
        Gate::define('view-evaluations', [SearchEvaluationPolicy::class, 'viewAny']);

        Gate::define('view-leaderboard', [SearchEvaluationPolicy::class, 'viewLeaderboard']);

        Gate::define('superuser', fn (User $user) => $user->super_admin);
    }
}
