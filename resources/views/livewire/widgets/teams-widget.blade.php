<x-widget-layout padding="px-3 py-2">

	<x-slot name="title">
		<!-- Widget Title -->
		<a href="{{ route('teams.all') }}" class="hover:bg-slate-100 dark:hover:bg-slate-700 px-3.5 py-2 rounded-md block hover:shadow dark:hover:shadow-md dark:hover:shadow-gray-900 transition-shadow ease-in-out">
			<div class="flex items-center gap-3">
				<h2 class="font-semibold text-slate-800 dark:text-slate-100">Teams</h2>
			</div>
		</a>
	</x-slot>

	<!-- Widget content -->
	<div class="overflow-x-auto">
		<table class="table-auto w-full dark:text-slate-300">

			<!-- Table header -->
			<thead class="text-xs uppercase text-left rtl:text-right text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-700 dark:bg-opacity-50 rounded-sm">
			<tr>
				<th class="p-2">
					<div class="font-semibold">
						{{ __('Team Name') }}
					</div>
				</th>
				<th class="p-2">
					<div class="font-semibold">
						{{ __('Team Owner') }}
					</div>
				</th>
				<th class="p-2">
					<div class="font-semibold">
						{{ __('Role') }}
					</div>
				</th>
				<th class="p-2">
					<div class="font-semibold">
						{{ __('Members') }}
					</div>
				</th>
				<th class="p-2">
					<div class="font-semibold">
						{{ __('Active') }}
					</div>
				</th>
			</tr>
			</thead>

			<!-- Table body -->
			<tbody class="text-sm font-medium divide-y divide-slate-100 dark:divide-slate-700">
			@foreach ($teams as $team)
				<!-- Row -->
				<tr wire:key="{{ $team->id }}" class="hover:bg-slate-100 dark:hover:bg-slate-800">
					<td class="p-2">
						<div class="text-slate-800 dark:text-slate-100">
							{{ $team->name }}
						</div>
					</td>
					<td class="p-2">
						<x-block.user-name :user="$team->owner" />
					</td>
					<td class="p-2">
						<x-block.user-role :team="$team" />
					</td>
					<td class="p-2">
						{{ $team->users->count() + 1 }}
					</td>
					<td class="p-2">
						@if ($team->id === Auth::user()->currentTeam->id)
							<svg class="mr-2 h-5 w-5 text-green-400" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
						@else
							<a href="javascript:void(0)" wire:click="switchTeam({{ $team->id }})" class="text-blue-600 dark:text-blue-500 hover:underline">
								{{ __('Switch') }}
							</a>
						@endif
					</td>
				</tr>
			@endforeach
			</tbody>
		</table>

	</div>
</x-widget-layout>
