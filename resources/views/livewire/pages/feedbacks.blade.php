<div>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
			<div class="flex items-center gap-2">
				User Feedback: <strong>{{ $evaluation->name }}</strong>

				<div class="mb-1">
					<!-- Evaluation Scale Type -->
					<livewire:evaluations.evaluation-scale-type :evaluation="$evaluation" key="evaluation-scale-type-{{ $evaluation->id }}" />
				</div>
			</div>
		</h2>
	</x-slot>

	<div>
		<!-- Navigation Tabs -->
		<x-block.navigation-tabs>
			<!-- Go Back -->
			<x-go-back href="{{ route('evaluation', [$evaluation->id]) }}" />
		</x-block.navigation-tabs>

		<!-- Feedbacks -->
		<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
			<div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
				<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">

					<div class="flex flex-wrap justify-between items-center gap-3">

						<!-- Left Column -->
						<div class="flex flex-wrap gap-3">
							<!-- Total Feedbacks -->
							<span class="font-semibold text-gray-400 dark:text-gray-400">
								{{ $feedbacks->total() }} user {{ Str::plural('feedback', $feedbacks->total()) }}
							</span>
						</div>

						<!-- Right Column -->
						<div class="flex flex-wrap gap-2">

							<!-- Progress Badge -->
							<livewire:evaluations.evaluation-progress
									:evaluation="$evaluation"
									total
									class="min-w-80"
									key="evaluation-progress-{{ $evaluation->id }}"
							/>
						</div>

					</div>

				</header>

				<!-- Second Header -->
				<div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">

					<div class="flex flex-wrap justify-between items-center gap-3">

						<!-- Left Column -->
						<div class="flex flex-wrap gap-3">
							<!-- Search -->
							<div class="relative">
								<div class="absolute inset-y-0 rtl:inset-r-0 start-0 flex items-center ps-3 pointer-events-none">
									<svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
										<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
									</svg>
								</div>
								<input wire:model.live.debounce.500ms="query" type="text" class="block pt-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search for items" />
							</div>
						</div>

						<!-- Right Column -->
						<div class="flex flex-wrap gap-2">
							<!-- Tags Filter -->
							<livewire:tags.filter-tags :tags="Auth::user()->currentTeam->tags" wire:model.live="filterTagId" key="feedbacks-filter-tags" />
						</div>


					</div>

				</div>

				<div class="p-3">
					<!-- Table and Filters -->
					<div class="sm:rounded-lg overflow-x-auto">
						<!-- Table -->
						<table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
							<thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
							<tr>
								<th scope="col" class="px-5 py-3">
									{{ __('Date') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('User') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Tags') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Keyword') }}
								</th>
								<th scope="col" class="px-5 py-3 text-center">
									{{ __('Position') }}
								</th>
								<th scope="col" class="px-5 py-3">
									{{ __('Doc') }}
								</th>
								<th scope="col" class="px-5 py-3 text-center">
									{{ __('Grade') }}
								</th>
								<th scope="col" class="px-5 py-3 text-right">
									{{ __('Action') }}
								</th>
							</tr>
							</thead>
							<tbody>
							@forelse ($feedbacks as $feedback)
								<tr wire:key="feedback-item-{{ $feedback->id }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
									<td class="px-5 py-4 align-baseline">
										<x-block.date-label :date="$feedback->updated_at" />
									</td>
									<td class="px-5 py-4 align-baseline">
										<x-block.user-name :user="$feedback->user" />
									</td>
									<td class="px-5 py-4 align-baseline">
										<x-tags.tags-list :tags="$feedback->user?->getTeamTags(Auth::user()->current_team_id) ?? []" />
									</td>
									<td class="px-5 py-4 align-baseline">
										<span class="font-semibold text-gray-700 dark:text-gray-300">
											<x-block.bordered-label class="font-bold">
												{{ $feedback->snapshot->keyword->keyword }}
											</x-block.bordered-label>
										</span>
									</td>
									<td class="px-5 py-4 align-baseline text-center">
										<x-typography.round-badge-blue :value="$feedback->snapshot->position" />
									</td>
									<td class="px-5 py-4 align-baseline">
										<a data-popover-target="popover-{{ $feedback->id }}" data-popover-trigger="click" href="javascript:void(0)" class="underline decoration-dotted">
											{{ $feedback->snapshot->name }}
										</a>

										<!-- Popover -->
										<div data-popover id="popover-{{ $feedback->id }}" role="tooltip" class="w-full sm:w-[500px] md:w-[600px] absolute z-10 invisible inline-block text-sm transition-opacity duration-300 opacity-0">
											<x-evaluations.snapshot-preview :snapshot="$feedback->snapshot" :show-position="false" />
										</div>
									</td>
									<td class="px-5 py-4 align-baseline text-center">
										<x-dynamic-component :component="$evaluation->getScale()->getScaleBadgeComponent()" :grade="$feedback->grade" size="lg" />
									</td>
									<td class="px-5 py-4 text-right align-baseline">
										<!-- Reset Button -->
										@if (!$evaluation->isFinished())
											<button
													class="btn ml-2 px-3 py-1 font-medium rounded-lg text-xs text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
													wire:click="resetFeedback('{{ $feedback->id }}')"
													wire:loading.attr="disabled"
											>
												Reset
											</button>
										@endif
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="7" class="px-5 py-4 text-center">
										<span class="text-gray-500 dark:text-gray-400">No feedback found</span>
									</td>
								</tr>
							@endforelse
							</tbody>
						</table>
						<nav class="items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
							{{ $feedbacks->links() }}
						</nav>
					</div>

				</div>
			</div>
		</div>
	</div>

</div>
