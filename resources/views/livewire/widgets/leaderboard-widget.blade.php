<x-widget-layout padding="px-3 py-2">

	<x-slot name="title">
		<!-- Widget Title -->
		<a href="{{ route('leaderboard') }}" class="hover:bg-slate-100 dark:hover:bg-slate-700 px-3.5 py-2 rounded-md block hover:shadow dark:hover:shadow-md dark:hover:shadow-gray-900 transition-shadow ease-in-out">
			<div class="flex items-center gap-3">
				<h2 class="font-semibold text-slate-800 dark:text-slate-100">Leaderboard</h2>
			</div>
			<div class="text-xs font-normal text-gray-500 dark:text-gray-300">Last 7 days</div>
		</a>
	</x-slot>

	<!-- Widget content -->
	<div class="overflow-x-auto">
		<div class="grow">
			@php($id = unique_key())
			<div class="h-[230px]">
				<canvas
						id="id-{{ $id }}"
						wire:key="key-{{ $id }}"
						data-leaderboard-chart="{{ json_encode($dataset) }}"
						width="500"
						height="230"
				></canvas>
			</div>
		</div>
	</div>
</x-widget-layout>
