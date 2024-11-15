@props(['dataset'])

<div
		class="flex flex-col col-span-full bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700"
>
	<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex gap-3 justify-between items-center">
		<!-- Chart Title -->
		<div class="flex items-center gap-3">
			<h2 class="font-semibold text-slate-800 dark:text-slate-100">Top 10</h2>
		</div>
	</header>

	<div class="grow">
		@php($id = unique_key())
		<div class="h-[400px]">
			<canvas
					id="id-{{ $id }}"
					wire:key="key-{{ $id }}"
					data-leaderboard-chart="{{ json_encode($dataset) }}"
					width="500"
					height="400"
			></canvas>
		</div>
	</div>
</div>
