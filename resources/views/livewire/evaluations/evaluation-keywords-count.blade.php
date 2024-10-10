<div>
	@if ($evaluation->successful_keywords)
		<span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">{{ $evaluation->successful_keywords }}</span>
	@endif
	@if ($evaluation->failed_keywords)
		<span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300">{{ $evaluation->failed_keywords }}</span>
	@endif

	@if ($evaluation->successful_keywords === null && $evaluation->failed_keywords === null)
		<span class="text-xs text-gray-400 dark:text-gray-500">
			Not started
		</span>
	@endif
</div>
