@if ($link)
	<a href="{{ route('evaluation.feedback', $evaluation->id) }}" data-popover-target="evaluation-progress-{{ $evaluation->id }}" class="inline-block">
@endif

<div class="{{ $class }} min-w-32 inline-flex text-sm gap-2.5 items-center px-2.5 py-2 rounded-lg border border-gray-250 bg-white dark:bg-gray-700 dark:border-gray-600 @if($link) hover:shadow-md dark:hover:shadow-gray-900 transition-shadow ease-in-out @endif">

	@if ($total)
		<div class="flex justify-end">
			<span class="text-xs font-medium text-gray-500 dark:text-gray-200 whitespace-nowrap">
				{{ $progress }}
			</span>
		</div>
	@endif

	<div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-800">
		<div class="bg-blue-600 dark:bg-blue-500 h-2.5 rounded-full" style="width: {{ round($evaluation->progress) }}%"></div>
	</div>

	<div class="flex justify-end">
		<span class="text-sm font-medium text-blue-700 dark:text-white">{{ round($evaluation->progress) }}%</span>
	</div>

</div>

@if ($link)
		<x-tooltip id="evaluation-progress-{{ $evaluation->id }}" with-arrow>
			<span class="whitespace-nowrap">
				Click to view user feedback
			</span>
		</x-tooltip>
	</a>
@endif
