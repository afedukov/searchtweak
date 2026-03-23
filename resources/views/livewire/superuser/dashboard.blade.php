<div>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
			{{ __('Platform Dashboard') }}
		</h2>
	</x-slot>

	<div>
		<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

			{{-- Section 1: Overview Stats --}}
			<div class="grid grid-cols-12 gap-6 mb-8">

				{{-- Users Card --}}
				<div class="col-span-12 sm:col-span-6 xl:col-span-3">
					<div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700 p-5">
						<div class="flex items-start">
							<div class="flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-500/20 mr-3 shrink-0 mt-1">
								<svg class="w-5 h-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
									<path d="M7.5 6.5C7.5 8.981 9.519 11 12 11s4.5-2.019 4.5-4.5S14.481 2 12 2 7.5 4.019 7.5 6.5zM20 21h1v-1c0-3.859-3.141-7-7-7h-4c-3.86 0-7 3.141-7 7v1h17z"/>
								</svg>
							</div>
							<div>
								<div class="text-3xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($overview['users_total']) }}</div>
								<div class="text-sm text-slate-500 dark:text-slate-400">
									Users
									@if ($overview['users_online'] > 0)
										<span class="inline-flex items-center gap-1 ml-1 text-green-600 dark:text-green-400">
											<span class="inline-block w-1.5 h-1.5 bg-green-500 rounded-full"></span>
											{{ $overview['users_online'] }} online
										</span>
									@endif
								</div>
							</div>
						</div>
					</div>
				</div>

				{{-- Feedback Card --}}
				<div class="col-span-12 sm:col-span-6 xl:col-span-3">
					<div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700 p-5">
						<div class="flex items-start">
							<div class="flex items-center justify-center w-10 h-10 rounded-full bg-sky-100 dark:bg-sky-500/20 mr-3 shrink-0 mt-1">
								<svg class="w-5 h-5 text-sky-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
									<path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
								</svg>
							</div>
							<div>
								<div class="text-3xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($overview['feedback_graded']) }}</div>
								<div class="text-sm text-slate-500 dark:text-slate-400">
									Feedback
									<span class="text-xs">({{ number_format($overview['feedback_judge_count']) }} <span class="text-[9px] font-bold uppercase tracking-wide px-1 py-0.5 rounded bg-indigo-500 text-white">AI</span> judge)</span>
								</div>
							</div>
						</div>
					</div>
				</div>

				{{-- Evaluations Card --}}
				<div class="col-span-12 sm:col-span-6 xl:col-span-3">
					<div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700 p-5">
						<div class="flex items-start">
							<div class="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-500/20 mr-3 shrink-0 mt-1">
								<svg class="w-5 h-5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
									<path d="M9 2 5 6v14c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2H9zm0 2h6v3H9V4zm-1 6h8v2H8v-2zm0 4h5v2H8v-2z"/>
								</svg>
							</div>
							<div>
								<div class="text-3xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($overview['evaluations_total']) }}</div>
								<div class="text-sm text-slate-500 dark:text-slate-400">
									Evaluations
									<span class="text-xs">({{ $overview['evaluations_active'] }} active)</span>
								</div>
							</div>
						</div>
					</div>
				</div>

				{{-- Judges Card --}}
				<div class="col-span-12 sm:col-span-6 xl:col-span-3">
					<div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700 p-5">
						<div class="flex items-start">
							<div class="flex items-center justify-center w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-500/20 mr-3 shrink-0 mt-1">
								<svg class="w-5 h-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
									<path d="M21 10.12h-6.78l2.74-2.82c-2.73-2.7-7.15-2.8-9.88-.1-2.73 2.71-2.73 7.08 0 9.79s7.15 2.71 9.88 0C18.32 15.65 19 14.08 19 12.1h2c0 1.98-.88 4.55-2.64 6.29-3.51 3.48-9.21 3.48-12.72 0-3.5-3.47-3.53-9.11-.02-12.58s9.14-3.49 12.65 0L21 3v7.12zM12.5 8v4.25l3.5 2.08-.72 1.21L11 13V8h1.5z"/>
								</svg>
							</div>
							<div>
								<div class="text-3xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($overview['judges_active']) }}</div>
								<div class="text-sm text-slate-500 dark:text-slate-400">
									<span class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-indigo-500 text-white">AI</span>
									Judges
									<span class="text-xs">({{ $overview['judges_providers_count'] }} {{ Str::plural('provider', $overview['judges_providers_count']) }})</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			{{-- Period Selector --}}
			<div class="flex items-center gap-3 mb-6">
				<div class="inline-flex rounded-md items-center" role="group">
					<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
						<input type="radio" wire:model.live="period" wire:loading.attr="disabled" name="dashboard-period" id="dashboard-period-7" value="7" class="hidden peer" />
						<label for="dashboard-period-7" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-s-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
							7 days
						</label>
					</div>
					<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
						<input type="radio" wire:model.live="period" wire:loading.attr="disabled" name="dashboard-period" id="dashboard-period-30" value="30" class="hidden peer" />
						<label for="dashboard-period-30" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border-t border-b border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
							30 days
						</label>
					</div>
					<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
						<input type="radio" wire:model.live="period" wire:loading.attr="disabled" name="dashboard-period" id="dashboard-period-90" value="90" class="hidden peer" />
						<label for="dashboard-period-90" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-e-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
							90 days
						</label>
					</div>
				</div>
			</div>

			{{-- Section 2: Activity Charts --}}
			<div class="grid grid-cols-12 gap-6 mb-8">
				{{-- New Users Chart --}}
				<div class="col-span-12 lg:col-span-6">
					<div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
						<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
							<h3 class="font-semibold text-slate-800 dark:text-slate-100">New Users</h3>
						</header>
						<div class="p-3">
							<div class="h-[250px]">
								<canvas
									data-admin-simple-bar-chart
									data-labels="{{ json_encode(array_keys($userRegistrations)) }}"
									data-values="{{ json_encode(array_values($userRegistrations)) }}"
									data-color="indigo"
								></canvas>
							</div>
						</div>
					</div>
				</div>

				{{-- Feedbacks Graded Chart --}}
				<div class="col-span-12 lg:col-span-6">
					<div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
						<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
							<h3 class="font-semibold text-slate-800 dark:text-slate-100">Feedback</h3>
						</header>
						<div class="p-3">
							<div class="h-[250px]">
								<canvas
									data-admin-line-chart

									data-labels="{{ json_encode(array_keys($feedbacksGraded)) }}"
									data-values="{{ json_encode(array_values($feedbacksGraded)) }}"
									data-color="sky"
								></canvas>
							</div>
						</div>
					</div>
				</div>
			</div>

			{{-- Section 3: Evaluations Breakdown --}}
			<div class="grid grid-cols-12 gap-6 mb-8">
				{{-- Metrics Distribution --}}
				<div class="col-span-12 md:col-span-4">
					<div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
						<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
							<h3 class="font-semibold text-slate-800 dark:text-slate-100">Metrics Distribution</h3>
						</header>
						<div class="p-3">
							<div class="h-[220px]">
								@if (count($metricsDistribution) > 0)
									<canvas
										data-admin-doughnut-chart

										data-labels="{{ json_encode(array_keys($metricsDistribution)) }}"
										data-values="{{ json_encode(array_values($metricsDistribution)) }}"
										data-colors="{{ json_encode(['#6366f1', '#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#14b8a6', '#f97316', '#ef4444']) }}"
									></canvas>
								@else
									<div class="flex items-center justify-center h-full">
										<span class="text-sm text-slate-400 dark:text-slate-500">No metrics yet</span>
									</div>
								@endif
							</div>
						</div>
					</div>
				</div>

				{{-- Evaluations by Scale --}}
				<div class="col-span-12 md:col-span-4">
					<div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
						<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
							<h3 class="font-semibold text-slate-800 dark:text-slate-100">Evaluations by Scale</h3>
						</header>
						<div class="p-3">
							<div class="h-[220px]">
								<canvas
									data-admin-doughnut-chart

									data-labels="{{ json_encode(array_map('ucfirst', array_keys($evaluationsByScale))) }}"
									data-values="{{ json_encode(array_values($evaluationsByScale)) }}"
									data-colors="{{ json_encode(['#3b82f6', '#8b5cf6', '#ec4899']) }}"
								></canvas>
							</div>
						</div>
					</div>
				</div>

				{{-- Feedback Sources --}}
				<div class="col-span-12 md:col-span-4">
					<div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
						<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
							<h3 class="font-semibold text-slate-800 dark:text-slate-100">Feedback Sources</h3>
						</header>
						<div class="p-3">
							<div class="h-[220px]">
								@if ($feedbackStats['graded'] > 0)
									<canvas
										data-admin-doughnut-chart
										data-labels="{{ json_encode(['Human', 'AI Judge']) }}"
										data-values="{{ json_encode([$feedbackStats['human_count'], $feedbackStats['judge_count']]) }}"
										data-colors="{{ json_encode(['#10b981', '#6366f1']) }}"
									></canvas>
								@else
									<div class="flex items-center justify-center h-full">
										<span class="text-sm text-slate-400 dark:text-slate-500">No feedback yet</span>
									</div>
								@endif
							</div>
						</div>
					</div>
				</div>
			</div>

			{{-- Section 4: AI Judge Monitoring --}}
			<div class="grid grid-cols-12 gap-6 mb-8">
				{{-- Success Rate by Provider --}}
				<div class="col-span-12 lg:col-span-6">
					<div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
						<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
							<h3 class="font-semibold text-slate-800 dark:text-slate-100"><span class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-indigo-500 text-white">AI</span> Judge Success Rate by Provider</h3>
						</header>
						<div class="p-3">
							<div class="h-[250px]">
								<canvas
									data-admin-bar-chart

									data-labels="{{ json_encode(array_keys($judgeSuccessRate)) }}"
									data-success="{{ json_encode(array_column($judgeSuccessRate, 'success')) }}"
									data-failed="{{ json_encode(array_column($judgeSuccessRate, 'failed')) }}"
								></canvas>
							</div>
						</div>
					</div>
				</div>

				{{-- Avg Latency --}}
				<div class="col-span-12 lg:col-span-6">
					<div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
						<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
							<h3 class="font-semibold text-slate-800 dark:text-slate-100">Avg <span class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-indigo-500 text-white">AI</span> Judge Latency (ms)</h3>
						</header>
						<div class="p-3">
							<div class="h-[250px]">
								<canvas
									data-admin-line-chart

									data-labels="{{ json_encode(array_keys($avgLatency)) }}"
									data-values="{{ json_encode(array_values($avgLatency)) }}"
									data-color="amber"
								></canvas>
							</div>
						</div>
					</div>
				</div>

				{{-- Token Usage Mini-Cards --}}
				<div class="col-span-12">
					<div class="grid grid-cols-12 gap-6">
						<div class="col-span-12 sm:col-span-4">
							<div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700 p-5">
								<div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Total Tokens Used</div>
								<div class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($tokenUsage['total_tokens']) }}</div>
							</div>
						</div>
						<div class="col-span-12 sm:col-span-4">
							<div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700 p-5">
								<div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Avg Tokens / Request</div>
								<div class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($tokenUsage['avg_per_request']) }}</div>
							</div>
						</div>
						<div class="col-span-12 sm:col-span-4">
							<div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700 p-5">
								<div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Top Provider by Tokens</div>
								<div class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ ucfirst($tokenUsage['top_provider']) }}</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			{{-- Section 5: Top Lists --}}
			<div class="grid grid-cols-12 gap-6">
				{{-- Most Active Teams --}}
				<div class="col-span-12 lg:col-span-6">
					<div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
						<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
							<h3 class="font-semibold text-slate-800 dark:text-slate-100">Most Active Teams</h3>
						</header>
						<div class="overflow-x-auto">
							<table class="table-auto w-full">
								<thead class="text-xs font-semibold uppercase text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-700/20">
									<tr>
										<th class="px-5 py-3 text-left whitespace-nowrap">Team</th>
										<th class="px-5 py-3 text-center whitespace-nowrap">Members</th>
										<th class="px-5 py-3 text-center whitespace-nowrap">Evaluations</th>
									</tr>
								</thead>
								<tbody class="text-sm divide-y divide-slate-100 dark:divide-slate-700">
									@forelse ($topTeams as $team)
										<tr>
											<td class="px-5 py-3">
												<span class="font-medium text-slate-800 dark:text-slate-100">{{ $team->name }}</span>
											</td>
											<td class="px-5 py-3 text-center text-slate-600 dark:text-slate-300">{{ $team->users_count }}</td>
											<td class="px-5 py-3 text-center text-slate-600 dark:text-slate-300">{{ $team->evaluations_count ?? 0 }}</td>
										</tr>
									@empty
										<tr>
											<td colspan="3" class="px-5 py-4 text-center text-slate-500 dark:text-slate-400">No teams yet</td>
										</tr>
									@endforelse
								</tbody>
							</table>
						</div>
					</div>
				</div>

				{{-- Recent Evaluations --}}
				<div class="col-span-12 lg:col-span-6">
					<div class="bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
						<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
							<h3 class="font-semibold text-slate-800 dark:text-slate-100">Recent Evaluations</h3>
						</header>
						<div class="overflow-x-auto">
							<table class="table-auto w-full">
								<thead class="text-xs font-semibold uppercase text-slate-400 dark:text-slate-500 bg-slate-50 dark:bg-slate-700/20">
									<tr>
										<th class="px-5 py-3 text-left whitespace-nowrap">Name</th>
										<th class="px-5 py-3 text-center whitespace-nowrap">Status</th>
										<th class="px-5 py-3 text-left whitespace-nowrap">Team</th>
										<th class="px-5 py-3 text-center whitespace-nowrap">Progress</th>
									</tr>
								</thead>
								<tbody class="text-sm divide-y divide-slate-100 dark:divide-slate-700">
									@forelse ($recentEvaluations as $evaluation)
										<tr>
											<td class="px-5 py-3">
												<span class="font-medium text-slate-800 dark:text-slate-100">{{ Str::limit($evaluation->name, 30) }}</span>
											</td>
											<td class="px-5 py-3 text-center">
												<livewire:evaluations.evaluation-status :evaluation="$evaluation" :key="'admin-eval-status-'.$evaluation->id" />
											</td>
											<td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ $evaluation->model?->team?->name ?? '—' }}</td>
											<td class="px-5 py-3">
												<livewire:evaluations.evaluation-progress :evaluation="$evaluation" :key="'admin-eval-progress-'.$evaluation->id" />
											</td>
										</tr>
									@empty
										<tr>
											<td colspan="4" class="px-5 py-4 text-center text-slate-500 dark:text-slate-400">No evaluations yet</td>
										</tr>
									@endforelse
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
</div>
