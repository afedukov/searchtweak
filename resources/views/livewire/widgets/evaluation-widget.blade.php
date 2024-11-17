<x-widget-layout padding="px-3 py-2">

	<x-slot name="title">
		<!-- Widget Title -->
		<a href="{{ route('evaluation', $evaluation->id) }}" class="hover:bg-slate-100 dark:hover:bg-slate-700 px-3.5 py-2 rounded-md block hover:shadow dark:hover:shadow-md dark:hover:shadow-gray-900 transition-shadow ease-in-out">
			<div class="flex items-center gap-3">
				<h2 class="font-semibold text-slate-800 dark:text-slate-100">Evaluation</h2>
			</div>
			<div class="text-xs font-normal text-gray-500 dark:text-gray-300">{{ $evaluation->name }}</div>
		</a>
	</x-slot>

	<!-- Widget content -->
	<div class="overflow-x-auto">
		<div class="px-5 py-3 gap-x-6 gap-y-2">

			<div class="mb-2 font-bold text-xs uppercase text-gray-700 dark:text-gray-200">Progress</div>

			<!-- Row #1 -->
			<div class="flex gap-6 justify-between items-center">
				<div class="w-full">
					<!-- Progress Badge -->
					<livewire:evaluations.evaluation-progress
							:evaluation="$evaluation"
							link
							total
							class="w-full min-w-48"
							key="evaluation-progress-{{ $evaluation->id }}"
					/>
				</div>

				<div class="flex items-baseline gap-1">
					<livewire:evaluations.evaluation-archived-badge :evaluation="$evaluation" key="evaluation-archived-badge-{{ $evaluation->id }}" />
					<livewire:evaluations.evaluation-status :evaluation="$evaluation" key="evaluation-status-{{ $evaluation->id }}" />
				</div>
			</div>

			<!-- Row #2 -->
			<div class="mt-6">
				<div class="mb-2 font-bold text-xs uppercase text-gray-700 dark:text-gray-200">Metrics</div>

				<!-- Evaluation Metrics -->
				<div class="flex flex-wrap gap-3" x-data="{ compact: $persist(false).as('compact-evaluation-widget-@js($evaluation->id)') }">
					@foreach ($evaluation->metrics as $metric)
						<x-metrics.evaluation-metric
								:metric="$metric"
								:keywords-count="$evaluation->keywords()->count()"
								:change="$metric->getChange(Auth::user()->currentTeam->baseline)"
						/>
					@endforeach
				</div>
			</div>

		</div>
	</div>
</x-widget-layout>
