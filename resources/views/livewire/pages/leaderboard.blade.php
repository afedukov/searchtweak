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
							<div class="inline-flex rounded-lg shadow-sm" role="group">
								<button type="button"
										wire:click="$set('filterType', 'all')"
										class="px-3 py-1.5 text-xs font-medium rounded-l-lg border {{ $filterType === 'all' ? 'bg-indigo-500 text-white border-indigo-500 dark:bg-indigo-600 dark:border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700' }}">
									All
								</button>
								<button type="button"
										wire:click="$set('filterType', 'users')"
										class="px-3 py-1.5 text-xs font-medium border-t border-b {{ $filterType === 'users' ? 'bg-indigo-500 text-white border-indigo-500 dark:bg-indigo-600 dark:border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700' }}">
									Users
								</button>
								<button type="button"
										wire:click="$set('filterType', 'judges')"
										class="px-3 py-1.5 text-xs font-medium rounded-r-lg border {{ $filterType === 'judges' ? 'bg-indigo-500 text-white border-indigo-500 dark:bg-indigo-600 dark:border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700' }}">
									Judges
								</button>
							</div>

							<!-- Total Count -->
							<span class="font-semibold text-gray-400 dark:text-gray-400">
								{{ $items->total() }} {{ Str::plural($showType === 'judges' ? 'judge' : 'entry', $items->total()) }}
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
										{{ $showType === 'judges' ? __('Judge') : __('User') }}
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
								<tr wire:key="leaderboard-item-{{ $showType === 'judges' ? 'j' . $item->judge_id : 'u' . $item->user_id }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
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
											@if ($showType === 'judges')
												<x-block.judge-name :judge="$item->judge" />
											@else
												<x-block.user-name :user="$item->user" />
											@endif
										</div>
									</td>
									@if ($showType !== 'judges')
										<td class="px-5 py-4 align-baseline">
											<x-block.user-role :user="$item->user" :team="$team" />
										</td>
									@endif
									<td class="px-5 py-4 align-baseline">
										@if ($showType === 'judges')
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

							{{-- In "All" mode, show judge entries after user entries --}}
							@if (($showType ?? '') === 'all' && isset($judgeItems) && $judgeItems->isNotEmpty())
								<tr class="bg-gray-50 dark:bg-gray-700/50">
									<td colspan="5" class="px-5 py-2">
										<span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">AI Judges</span>
									</td>
								</tr>
								@foreach ($judgeItems as $judgeItem)
									<tr wire:key="leaderboard-judge-{{ $judgeItem->judge_id }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
										<th scope="row" class="px-5 py-4 font-medium text-gray-900 dark:text-white align-baseline">
											<x-typography.round-badge-blue :value="$judgeItem->position" />
										</th>
										<td class="px-5 py-4 align-baseline">
											<div class="inline-flex justify-center items-center">
												<x-block.judge-name :judge="$judgeItem->judge" :show-badge="false" />
											</div>
										</td>
										<td class="px-5 py-4 align-baseline">
											<x-block.judge-role />
										</td>
										<td class="px-5 py-4 align-baseline">
											<x-tags.tags-list :tags="$judgeItem->judge?->tags ?? collect()" />
										</td>
										<td class="px-5 py-4 align-baseline">
											<span class="text-sm font-semibold text-gray-900 dark:text-white">
												{{ $judgeItem->feedback_count }}
											</span>
										</td>
									</tr>
								@endforeach
							@endif
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
