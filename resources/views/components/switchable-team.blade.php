@props(['team', 'component' => 'dropdown-link'])

<form method="POST" action="{{ route('current-team.update') }}">
    @method('PUT')
    @csrf

    <!-- Hidden Team ID -->
    <input type="hidden" name="team_id" value="{{ $team->id }}">
	<input type="hidden" name="redirect" value="{{ Route::currentRouteName() }}">

    <x-dynamic-component :component="$component" href="javascript:void(0)" onclick="event.preventDefault(); this.closest('form').submit();">
        <div class="flex items-center dark:text-slate-300">
            @if (Auth::user()->isCurrentTeam($team))
                <svg class="mr-2 h-5 w-5 text-green-400" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            @endif

            <div class="truncate">{{ $team->name }}</div>
        </div>
    </x-dynamic-component>
</form>
