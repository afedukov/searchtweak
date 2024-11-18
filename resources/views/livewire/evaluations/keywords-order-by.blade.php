<div class="relative flex" x-data="{ open: false }">
	<button
			class="relative btn bg-white dark:bg-slate-800 border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600 text-slate-500 hover:text-slate-600 dark:text-slate-400 dark:hover:text-slate-300"
			aria-haspopup="true"
			@click.prevent="open = !open"
			:aria-expanded="open"
	>
		<span class="sr-only">Order By</span><wbr>
		<i class="{{ $directions[$orderBy->getDirection()]['icon'] }}"></i>

		<div class="inline text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase ml-2">
			Order By {{ $label }}
		</div>

		<!-- Applied badge -->
		@if (!$orderBy->isDefault())
			<x-block.filter-applied-badge />
		@endif
	</button>
	<div
			class="origin-top-right z-30 absolute top-full min-w-64 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 pt-1.5 rounded shadow-lg overflow-hidden mt-1 sm:left-auto sm:right-0"
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
		<div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase pt-1.5 pb-2 px-3">Order By</div>

		<div class="flex flex-wrap gap-2 px-3 pb-3">
			<label
					@class([
						'text-xs px-3 py-2 text-gray-500 border border-gray-250 rounded-lg dark:text-gray-300 bg-white dark:bg-gray-700 dark:border-gray-600 cursor-pointer font-semibold whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 focus:ring-offset-white dark:focus:ring-offset-slate-800 hover:ring-2 hover:ring-offset-2 hover:ring-blue-500 hover:ring-offset-white dark:hover:ring-offset-slate-800',
						'ring-2 ring-offset-2 ring-blue-500 ring-offset-white dark:ring-offset-slate-800' => $orderBy->getMetricId() === \App\DTO\OrderBy::ORDER_BY_DEFAULT,
					])
					@click.prevent="
						$wire.set('orderBy.metricId', {{ \App\DTO\OrderBy::ORDER_BY_DEFAULT }});
						open = false;
					"
			>
				Default
			</label>
			<label
					@class([
						'text-xs px-3 py-2 text-gray-500 border border-gray-250 rounded-lg dark:text-gray-300 bg-white dark:bg-gray-700 dark:border-gray-600 cursor-pointer font-semibold whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 focus:ring-offset-white dark:focus:ring-offset-slate-800 hover:ring-2 hover:ring-offset-2 hover:ring-blue-500 hover:ring-offset-white dark:hover:ring-offset-slate-800',
						'ring-2 ring-offset-2 ring-blue-500 ring-offset-white dark:ring-offset-slate-800' => $orderBy->getMetricId() === \App\DTO\OrderBy::ORDER_BY_KEYWORD,
					])
					@click.prevent="
						$wire.set('orderBy.metricId', {{ \App\DTO\OrderBy::ORDER_BY_KEYWORD }});
						open = false;
					"
			>
				Keyword
			</label>
		</div>

		<div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase pt-1.5 pb-2 px-3">Metric</div>

		<!-- Metrics -->
		<div class="flex flex-wrap gap-2 px-3 pb-3">
			@forelse ($metrics as $id => $metricName)
				<label
						@class([
							'text-xs px-3 py-2 text-gray-500 border border-gray-250 rounded-lg dark:text-gray-300 bg-white dark:bg-gray-700 dark:border-gray-600 cursor-pointer font-semibold whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 focus:ring-offset-white dark:focus:ring-offset-slate-800 hover:ring-2 hover:ring-offset-2 hover:ring-blue-500 hover:ring-offset-white dark:hover:ring-offset-slate-800',
							'ring-2 ring-offset-2 ring-blue-500 ring-offset-white dark:ring-offset-slate-800' => $id == $orderBy->getMetricId(),
						])
						@click.prevent="
							$wire.set('orderBy.metricId', {{ Js::from($id) }});
							open = false;
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

		<div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase pt-1.5 pb-2 px-3">Direction</div>

		<div class="flex flex-wrap gap-2 px-3 pb-3">
			@foreach ($directions as $key => $direction)
				<label
						@class([
							'text-xs px-3 py-2 text-gray-500 border border-gray-250 rounded-lg dark:text-gray-300 bg-white dark:bg-gray-700 dark:border-gray-600 cursor-pointer font-semibold whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 focus:ring-offset-white dark:focus:ring-offset-slate-800 hover:ring-2 hover:ring-offset-2 hover:ring-blue-500 hover:ring-offset-white dark:hover:ring-offset-slate-800',
							'ring-2 ring-offset-2 ring-blue-500 ring-offset-white dark:ring-offset-slate-800' => $key == $orderBy->getDirection(),
						])
						@click.prevent="
							$wire.set('orderBy.direction', {{ Js::from($key) }});
							open = false;
						"
				>
					<i class="{{ $direction['icon'] }}"></i> {{ $direction['label'] }}
				</label>
			@endforeach
		</div>

		<div class="py-2 px-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/20">
			<ul class="flex items-center justify-between">
				<li>
					<button @click="$wire.orderBy.metricId = {{ \App\DTO\OrderBy::ORDER_BY_DEFAULT }}; $wire.orderBy.direction = 'asc'; open = false" class="btn-xs bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 text-slate-500 dark:text-slate-300 hover:text-slate-600 dark:hover:text-slate-200">
						{{ __('Reset') }}
					</button>
				</li>
			</ul>
		</div>
	</div>
</div>
