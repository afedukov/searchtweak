<x-widget-layout padding="px-3 py-2" cols="4">

	<x-slot name="title">
		<!-- Metric Title -->
		<a href="{{ route('evaluation', [$evaluation->id]) }}" class="hover:bg-slate-100 dark:hover:bg-slate-700 px-3.5 py-2 rounded-md block hover:shadow dark:hover:shadow-md dark:hover:shadow-gray-900 transition-shadow ease-in-out">
			<div class="flex items-center gap-3">
				<h2 class="font-semibold text-slate-800 dark:text-slate-100">{{ $evaluation->name }}</h2>
			</div>
		</a>
	</x-slot>

	<!-- Widget content -->
	<div class="overflow-x-auto">
		<div class="px-5 py-3">
			<!-- Progress Badge -->
			<livewire:evaluations.evaluation-progress
					:evaluation="$evaluation"
					link
					total
					class="w-full"
					wire:key="{{ md5(mt_rand()) }}"
			/>

			<!-- Info icon and evaluation description -->
			@if ($evaluation->description)
				<div class="flex gap-3 mt-8">
					<i class="fa-regular fa-file-lines text-gray-500 dark:text-gray-300 text-lg mt-0.5"></i>

					<!-- Evaluation description -->
					<div class="text-sm text-gray-500 dark:text-gray-300">
						{{ $evaluation->description }}
					</div>
				</div>
			@endif

		</div>
	</div>
</x-widget-layout>
