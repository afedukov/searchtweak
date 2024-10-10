@props(['user', 'photo' => true])

<div class="inline-flex justify-center items-center">
	@if ($photo)
		<div class="inline-block w-8 h-8">
			<img class="w-8 h-8 rounded-full inline" src="{{ $user?->profile_photo_url ?? \App\Services\Helpers::getRemovedUserProfilePhotoUrl() }}" width="32" height="32" alt="{{ $user?->name ?? 'Removed User' }}">
		</div>
	@endif
	<div class="flex items-center truncate">
		<span class="truncate ml-2 text-sm font-medium dark:text-slate-300 group-hover:text-slate-800 dark:group-hover:text-slate-200">
			{{ $user->name ?? 'Removed User' }}
		</span>
		@if ($user)
			@if ($user?->isOnline())
				<span class="inline-block w-2 h-2 bg-green-400 rounded-full ml-2"></span>
			@else
				<span class="inline-block w-2 h-2 bg-gray-400 rounded-full ml-2"></span>
			@endif
		@endif
	</div>
</div>
