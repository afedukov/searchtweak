<div>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
			<span class="uppercase tracking-wide px-1.5 py-0.5 rounded bg-indigo-500 text-white ml-2">AI</span>
			@if ($judge !== null)
				<span class="inline-flex items-center gap-2">
					<span>{{ __('Judges') }} /</span>
					<x-block.judge-name
						:judge="$judge"
						icon-size="sm"
						name-class="text-xl font-semibold text-gray-700 dark:text-slate-300"
					/>
					<span>{{ __('Logs') }}</span>
				</span>
			@else
				{{ __('Judge Logs') }}
			@endif
		</h2>
	</x-slot>

	<div x-data="{ showLog: false, currentLog: null, activeTab: 'request' }">

		<!-- Navigation -->
		<x-block.navigation-tabs>
			<x-go-back href="{{ route('judges') }}" />
		</x-block.navigation-tabs>

		<!-- Logs -->
		<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
			<div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">

				<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
					<div class="flex flex-wrap justify-between items-center gap-3">

						<!-- Left: total -->
						<div class="flex flex-wrap gap-3">
							<span class="font-semibold text-gray-400 dark:text-gray-400">
								{{ $logs->total() }} {{ Str::plural('entry', $logs->total()) }}
							</span>
						</div>

						<!-- Right: filters -->
						<div class="flex flex-wrap gap-2 items-center">

							<!-- Status filter -->
							<div class="inline-flex rounded-md items-center" role="group">
								<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
									<input type="radio" wire:model.live="filterStatus" wire:loading.attr="disabled" name="judge-logs-filter" id="judge-logs-status-all" value="all" class="hidden peer" />
									<label for="judge-logs-status-all" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-s-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
										{{ __('All') }}
									</label>
								</div>
								<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
									<input type="radio" wire:model.live="filterStatus" wire:loading.attr="disabled" name="judge-logs-filter" id="judge-logs-status-success" value="success" class="hidden peer" />
									<label for="judge-logs-status-success" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border-t border-b border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
										{{ __('Successful') }}
										<span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">{{ $countSuccessful }}</span>
									</label>
								</div>
								<div class="flex" wire:loading.class="opacity-50 pointer-events-none">
									<input type="radio" wire:model.live="filterStatus" wire:loading.attr="disabled" name="judge-logs-filter" id="judge-logs-status-error" value="error" class="hidden peer" />
									<label for="judge-logs-status-error" class="px-4 py-2 cursor-pointer peer-checked:z-10 peer-checked:ring-1 peer-checked:ring-blue-700 peer-checked:text-blue-700 dark:peer-checked:ring-blue-500 dark:peer-checked:text-white text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase bg-white border border-gray-200 rounded-e-md hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-800 dark:border-gray-700 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-blue-500 dark:focus:text-white">
										{{ __('Failed') }}
										<span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300">{{ $countFailed }}</span>
									</label>
								</div>
							</div>

							<!-- Judge filter (global mode only) -->
							@if ($judge === null)
								<select
										wire:model.live="filterJudgeId"
										class="text-sm border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 rounded px-2 py-1.5 focus:ring-2 focus:ring-indigo-500"
								>
									<option value="0">{{ __('All judges') }}</option>
									@foreach ($judgeOptions as $j)
										<option value="{{ $j->id }}">{{ $j->name }}</option>
									@endforeach
								</select>
							@endif

							<!-- Evaluation filter -->
							<select
									wire:model.live="filterEvaluationId"
									class="text-sm border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 rounded px-2 py-1.5 focus:ring-2 focus:ring-indigo-500"
							>
								<option value="0">{{ __('All evaluations') }}</option>
								@foreach ($evaluationOptions as $ev)
									<option value="{{ $ev->id }}">{{ $ev->name }}</option>
								@endforeach
							</select>

							<!-- Date Range -->
							<x-datepicker id="flatpickr-judge-logs-dates" wire:model="date" />

							<!-- Export JSONL -->
							<button
									wire:click.prevent="exportJsonl"
									wire:loading.attr="disabled"
									class="flex items-center justify-center flex-shrink-0 px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded hover:bg-gray-100 hover:text-primary-700 focus:z-10 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
							>
								<svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewbox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
									<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
								</svg>
								{{ __('Export JSONL') }}
							</button>

							<!-- Reset -->
							<button
									wire:click="resetFilters"
									class="flex items-center justify-center flex-shrink-0 px-3 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded hover:bg-gray-100 hover:text-primary-700 focus:z-10 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
							>
								{{ __('Reset') }}
							</button>

						</div>
					</div>
				</header>

				<div class="p-3">
					<div class="sm:rounded-lg overflow-x-auto">
						<table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
							<thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
							<tr>
								<th scope="col" class="px-5 py-3">{{ __('Date') }}</th>
								@if ($judge === null)
									<th scope="col" class="px-5 py-3">{{ __('Judge') }}</th>
								@endif
								<th scope="col" class="px-5 py-3 text-center">{{ __('Status') }}</th>
								<th scope="col" class="px-5 py-3 text-center">{{ __('HTTP') }}</th>
								<th scope="col" class="px-5 py-3">{{ __('Evaluation') }}</th>
								<th scope="col" class="px-5 py-3 text-center">{{ __('Scale') }}</th>
								<th scope="col" class="px-5 py-3 text-center">{{ __('Batch') }}</th>
								<th scope="col" class="px-5 py-3 text-center">{{ __('Latency') }}</th>
								<th scope="col" class="px-5 py-3 text-center">{{ __('Tokens') }}</th>
								<th scope="col" class="px-5 py-3 text-right">{{ __('View') }}</th>
							</tr>
							</thead>
							<tbody>
							@forelse ($logs as $log)
								<tr wire:key="log-{{ $log->id }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">

									<!-- Date -->
									<td class="px-5 py-4 align-baseline whitespace-nowrap">
										<x-block.date-label :date="$log->created_at" />
									</td>

									<!-- Judge name (global mode) -->
									@if ($judge === null)
										<td class="px-5 py-4 align-baseline">
											@if ($log->judge)
												<x-block.judge-name
													:judge="$log->judge"
													icon-size="sm"
													name-class="text-sm font-medium text-gray-700 dark:text-gray-300"
												/>
											@else
												<span class="text-xs text-gray-400 dark:text-gray-600 italic">{{ __('Judge deleted') }}</span>
												<div class="font-mono text-xs text-gray-400 dark:text-gray-600">{{ $log->provider }}/{{ $log->model }}</div>
											@endif
										</td>
									@endif

									<!-- Status badge -->
									<td class="px-5 py-4 align-baseline text-center">
										@if ($log->isSuccessful())
											<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
												{{ __('OK') }}
											</span>
										@else
											<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
												{{ __('Error') }}
											</span>
										@endif
									</td>

									<!-- HTTP status code -->
									<td class="px-5 py-4 align-baseline text-center font-mono text-xs">
										{{ $log->http_status_code ?? '—' }}
									</td>

									<!-- Evaluation -->
									<td class="px-5 py-4 align-baseline">
										@if ($log->evaluation)
											<a href="{{ route('evaluation', $log->evaluation->id) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">{{ $log->evaluation->name }}</a>
										@else
											<span class="text-xs text-gray-400 dark:text-gray-600 italic">{{ __('Evaluation deleted') }}</span>
										@endif
									</td>

									<!-- Scale -->
									<td class="px-5 py-4 align-baseline text-center">
										@if ($log->scale_type)
											<x-metrics.scale-type
												:scaleType="$log->scale_type"
												:scaleName="\App\Services\Scorers\Scales\ScaleFactory::create($log->scale_type)->getName()"
											/>
										@else
											<span class="text-gray-400">—</span>
										@endif
									</td>

									<!-- Batch size -->
									<td class="px-5 py-4 align-baseline text-center">
										<span class="font-mono text-xs">{{ $log->batch_size ?? '—' }}</span>
									</td>

									<!-- Latency -->
									<td class="px-5 py-4 align-baseline text-center whitespace-nowrap">
										@if ($log->latency_ms !== null)
											<span class="font-mono text-xs">{{ number_format($log->latency_ms) }} ms</span>
										@else
											<span class="text-gray-400">—</span>
										@endif
									</td>

									<!-- Tokens -->
									<td class="px-5 py-4 align-baseline text-center whitespace-nowrap">
										@if ($log->total_tokens !== null)
											<span class="font-mono text-xs" title="{{ __('Prompt') }}: {{ $log->prompt_tokens }} / {{ __('Completion') }}: {{ $log->completion_tokens }}">
												{{ number_format($log->total_tokens) }}
											</span>
										@else
											<span class="text-gray-400">—</span>
										@endif
									</td>

									<!-- View button -->
									<td class="px-5 py-4 align-baseline text-right">
										<button
												@click="
													currentLog = {{ Js::from([
														'request_url'   => $log->request_url,
														'request_body'  => $log->request_body,
														'response_body' => $log->response_body,
														'error_message' => $log->error_message,
													]) }};
													activeTab = {{ $log->error_message ? "'error'" : "'request'" }};
													showLog = true;
												"
												class="btn-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-indigo-400 text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400"
										>
											{{ __('View') }}
										</button>
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="{{ $judge !== null ? 9 : 10 }}" class="px-5 py-4 text-center">
										<span class="text-gray-400 dark:text-gray-500">{{ __('No logs found') }}</span>
									</td>
								</tr>
							@endforelse
							</tbody>
						</table>

						<nav class="items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
							{{ $logs->links() }}
						</nav>
					</div>
				</div>

			</div>
		</div>

		<!-- Log Detail Modal -->
		<x-modals.modal-alpine var="showLog" maxWidth="5xl">
			<div class="px-6 py-4">

				<!-- Modal header -->
				<div class="flex items-center justify-between mb-4">
					<h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('Request / Response') }}</h3>
					<button @click="showLog = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
						<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
							<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
						</svg>
					</button>
				</div>

				<!-- URL -->
				<template x-if="currentLog">
					<div>
						<p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">{{ __('Request URL') }}</p>
						<p class="font-mono text-xs break-all text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900 rounded px-3 py-2 mb-4" x-text="currentLog.request_url"></p>

						<!-- Tabs -->
						<div class="border-b border-gray-200 dark:border-gray-700 mb-4">
							<ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500 dark:text-gray-400">
								<li class="mr-2">
									<button
											@click="activeTab = 'request'"
											:class="activeTab === 'request' ? 'text-indigo-600 border-indigo-600 dark:text-indigo-500 dark:border-indigo-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'"
											class="inline-block p-4 border-b-2 rounded-t-lg"
									>{{ __('Request Body') }}</button>
								</li>
								<li class="mr-2">
									<button
											@click="activeTab = 'response'"
											:class="activeTab === 'response' ? 'text-indigo-600 border-indigo-600 dark:text-indigo-500 dark:border-indigo-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'"
											class="inline-block p-4 border-b-2 rounded-t-lg"
									>{{ __('Response Body') }}</button>
								</li>
								<template x-if="currentLog.error_message">
									<li>
										<button
												@click="activeTab = 'error'"
												:class="activeTab === 'error' ? 'text-red-600 border-red-600 dark:text-red-500 dark:border-red-500' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'"
												class="inline-block p-4 border-b-2 rounded-t-lg"
										>{{ __('Error') }}</button>
									</li>
								</template>
							</ul>
						</div>

						<!-- Tab contents -->
						<div x-show="activeTab === 'request'">
							<pre class="overflow-auto max-h-96 whitespace-pre-wrap font-mono text-xs bg-gray-50 dark:bg-gray-900 rounded p-3 text-gray-800 dark:text-gray-200" x-text="currentLog.request_body"></pre>
						</div>
						<div x-show="activeTab === 'response'">
							<template x-if="currentLog.response_body">
								<pre class="overflow-auto max-h-96 whitespace-pre-wrap font-mono text-xs bg-gray-50 dark:bg-gray-900 rounded p-3 text-gray-800 dark:text-gray-200" x-text="currentLog.response_body"></pre>
							</template>
							<template x-if="!currentLog.response_body">
								<p class="text-gray-400 text-sm italic">{{ __('No response body') }}</p>
							</template>
						</div>
						<template x-if="currentLog.error_message">
							<div x-show="activeTab === 'error'">
								<pre class="overflow-auto max-h-96 whitespace-pre-wrap font-mono text-xs bg-red-50 dark:bg-red-950 rounded p-3 text-red-800 dark:text-red-300" x-text="currentLog.error_message"></pre>
							</div>
						</template>
					</div>
				</template>

			</div>
		</x-modals.modal-alpine>

	</div>
</div>
