<div
		class="relative flex"
		x-data="{
			open: false,
			excluded: excludedMetrics
		}"
>
	<button
			class="relative btn bg-white dark:bg-slate-800 border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600 text-slate-500 hover:text-slate-600 dark:text-slate-400 dark:hover:text-slate-300"
			aria-haspopup="true"
			@click.prevent="open = !open"
			:aria-expanded="open"
	>
		<span class="sr-only">Select Metrics</span><wbr>

		<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 fill-current">
			<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
		</svg>

		<div class="inline text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase ml-2">Select Metrics</div>

		<!-- Applied badge -->
		<template x-if="excludedMetrics.length > 0">
			<x-block.filter-applied-badge />
		</template>
	</button>
	<div
			class="origin-top-right z-30 absolute top-full min-w-80 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 pt-1.5 rounded shadow-lg overflow-hidden mt-1 sm:left-auto sm:right-0"
			@click.outside="open = false"
			@keydown.escape.window="open = false"
			x-show="open"
			x-transition:enter="transition ease-out duration-200 transform"
			x-transition:enter-start="opacity-0 -translate-y-2"
			x-transition:enter-end="opacity-100 translate-y-0"
			x-transition:leave="transition ease-out duration-200"
			x-transition:leave-start="opacity-100"
			x-transition:leave-end="opacity-0"
			x-cloak
	>
		<div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase pt-1.5 pb-2 px-3">Select Metrics</div>

		<!-- Metrics -->
		<div class="flex flex-wrap gap-2.5 px-3 pb-3">
			@forelse ($metrics as $id => $metricName)
				<label
						class="text-xs px-3 py-2 text-gray-500 border border-gray-250 rounded-lg dark:text-gray-300 bg-white dark:bg-gray-700 dark:border-gray-600 cursor-pointer font-semibold whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 focus:ring-offset-white dark:focus:ring-offset-slate-800 hover:ring-2 hover:ring-offset-2 hover:ring-blue-500 hover:ring-offset-white dark:hover:ring-offset-slate-800"
						:class="!excluded.includes({{ $id }}) ? 'ring-2 ring-offset-2 ring-blue-500 ring-offset-white dark:ring-offset-slate-800' : ''"
						@click.prevent="
							if (excluded.includes({{ $id }})) {
								excluded = excluded.filter(id => id !== {{ $id }});
							} else {
								excluded = [...excluded, {{ $id }}];
							}
						"
				>
					{{ $metricName }}
				</label>
			@empty
				<span class="text-xs text-gray-400 dark:text-gray-500">
					{{ __('No metrics') }}
				</span>
			@endforelse
		</div>

		<div class="py-2 px-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/20">
			<ul class="flex items-center justify-between">
				<li>
					<button @click="excludedMetrics = excluded = []; open = false" class="btn-xs bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 text-slate-500 dark:text-slate-300 hover:text-slate-600 dark:hover:text-slate-200">
						{{ __('Reset') }}
					</button>
				</li>
				<li>
					<button @click="excludedMetrics = excluded; open = false" @focusout="open = false" class="btn-xs bg-indigo-500 hover:bg-indigo-600 text-white">
						{{ __('Apply') }}
					</button>
				</li>
			</ul>
		</div>
	</div>
</div>
