@props(['user' => Auth::user(), 'team' => null, 'manage' => false])

@if ($manage)
	<a href="#" wire:click.prevent="manageRole('{{ $user->id }}')">
@endif
<span @class([
	'text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-slate-700 border dark:border-slate-600',
	'bg-green-100 text-green-800 dark:text-green-400 border-green-400 dark:border-green-400' => $user->isAdmin($team),
	'bg-purple-100 text-purple-800 dark:text-purple-400 border-purple-400 dark:border-purple-400' => $user->isEvaluator($team),
	'bg-blue-100 text-blue-800 dark:text-blue-400 border-blue-400 dark:border-blue-400' => $user->isOwner($team),
])>
	{{ $user->teamRole($team)?->name ?? 'Not in team' }}
</span>
@if ($manage)
	</a>
@endif
