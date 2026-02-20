<div>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
			<div class="flex items-center gap-2">
				Leaderboard
			</div>
		</h2>
	</x-slot>

	<div>
		<!-- Leaderboard -->
		<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

			<!-- Widgets -->
			<div class="grid grid-cols-12 gap-6 mb-8">

				<!-- Leaderboard Chart -->
				<x-leaderboard.leaderboard-chart :dataset="$dataset" />

			</div>

			<div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
				<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">

					<div class="flex flex-wrap justify-between items-center gap-3">

						<!-- Left Column -->
						<div class="flex flex-wrap items-center gap-3">

							<!-- Type Filter -->
							<div class="inline-flex rounded-md items-center" role="group">
								<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
									<input type="radio" wire:model.live="filterType" wire:loading.attr="disabled" name="leaderboard-filter-type" id="leaderboard-filter-type-all" value="all" class="hidden peer" />
									<label for="leaderboard-filter-type-all" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-s-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
										{{ __('All') }}
									</label>
								</div>
								<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
									<input type="radio" wire:model.live="filterType" wire:loading.attr="disabled" name="leaderboard-filter-type" id="leaderboard-filter-type-users" value="users" class="hidden peer" />
									<label for="leaderboard-filter-type-users" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border-t border-b border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
										{{ __('Users') }}
									</label>
								</div>
								<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
									<input type="radio" wire:model.live="filterType" wire:loading.attr="disabled" name="leaderboard-filter-type" id="leaderboard-filter-type-judges" value="judges" class="hidden peer" />
									<label for="leaderboard-filter-type-judges" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-e-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
										<span class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-indigo-500 text-white mr-1">AI</span>
										{{ __('Judges') }}
									</label>
								</div>
							</div>

							<!-- Total Count -->
							<span class="font-semibold text-gray-400 dark:text-gray-400">
								{{ $items->total() }} {{ Str::plural('entry', $items->total()) }}
							</span>
						</div>

						<!-- Right Column -->
						<div class="flex flex-wrap gap-2">

							<!-- Tags Filter -->
							<livewire:tags.filter-tags :tags="Auth::user()->currentTeam->tags" wire:model.live="filterTagId" key="team-filter-tags" />

							<!-- Datepicker -->
							<x-datepicker id="flatpickr-dates-filter" wire:model="date" />

						</div>

					</div>

				</header>
				<div class="p-3">

					<!-- Table and Filters -->
					<div class="sm:rounded-lg overflow-x-auto">

						<!-- Table -->
						<table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
							<thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
								<tr>
									<th scope="col" class="px-5 py-3">
										{{ __('Rank') }}
									</th>
									<th scope="col" class="px-5 py-3">
										{{ $showType === 'all' ? __('Participant') : ($showType === 'judges' ? __('Judge') : __('User')) }}
									</th>
									@if ($showType !== 'judges')
										<th scope="col" class="px-5 py-3">
											{{ __('Role') }}
										</th>
									@endif
									<th scope="col" class="px-5 py-3">
										{{ __('Tags') }}
									</th>
									<th scope="col" class="px-5 py-3">
										{{ __('Feedback') }}
									</th>
								</tr>
							</thead>
							<tbody>
							@forelse ($items as $item)
								<tr wire:key="leaderboard-item-{{ ($showType === 'all' ? ($item->entry_type === 'judges' ? 'j' . ($item->judge?->id ?? $loop->index) : 'u' . ($item->user?->id ?? $loop->index)) : ($showType === 'judges' ? 'j' . $item->judge_id : 'u' . $item->user_id)) }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
									<th scope="row" class="px-5 py-4 font-medium text-gray-900 dark:text-white align-baseline">
										@if ($item->position == 1)
											<x-typography.round-badge value="1" class="text-white bg-amber-400 dark:bg-amber-500" />
										@elseif ($item->position == 2)
											<x-typography.round-badge value="2" class="text-white bg-gray-400 dark:bg-gray-500" />
										@elseif ($item->position == 3)
											<x-typography.round-badge value="3" class="text-white bg-yellow-500 dark:bg-yellow-600" />
										@else
											<x-typography.round-badge-blue :value="$item->position" />
										@endif
									</th>
									<td class="px-5 py-4 align-baseline">
										<div class="inline-flex justify-center items-center">
											@if ($showType === 'judges' || ($showType === 'all' && $item->entry_type === 'judges'))
												<x-block.judge-name :judge="$item->judge" :show-badge="$showType !== 'all'" />
											@else
												<x-block.user-name :user="$item->user" />
											@endif
										</div>
									</td>
									@if ($showType !== 'judges')
										<td class="px-5 py-4 align-baseline">
											@if ($showType === 'all' && $item->entry_type === 'judges')
												<x-block.judge-role />
											@else
												<x-block.user-role :user="$item->user" :team="$team" />
											@endif
										</td>
									@endif
									<td class="px-5 py-4 align-baseline">
										@if ($showType === 'judges' || ($showType === 'all' && $item->entry_type === 'judges'))
											<x-tags.tags-list :tags="$item->judge?->tags ?? collect()" />
										@else
											<x-tags.tags-list :tags="$item->user->getTeamTags(Auth::user()->current_team_id)" />
										@endif
									</td>
									<td class="px-5 py-4 align-baseline">
										<span class="text-sm font-semibold text-gray-900 dark:text-white">
											{{ $item->feedback_count }}
										</span>
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="{{ $showType === 'judges' ? 4 : 5 }}" class="px-5 py-4 text-center">
										<span class="text-gray-400 dark:text-gray-500">
											{{ __('No entries found') }}
										</span>
									</td>
								</tr>
							@endforelse
							</tbody>
						</table>

						<!-- Navigation -->
						<nav class="items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
							{{ $items->links() }}
						</nav>

					</div>

				</div>
			</div>
		</div>
	</div>

	<!-- Modals -->

</div>
