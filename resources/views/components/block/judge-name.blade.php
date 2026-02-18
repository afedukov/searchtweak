@props(['judge', 'showBadge' => true])

<div class="inline-flex justify-center items-center">
	<div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 flex-shrink-0">
		<svg class="w-4 h-4 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
			<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
		</svg>
	</div>
	<div class="flex items-center truncate">
		<span class="truncate ml-2 text-sm font-medium dark:text-slate-300 group-hover:text-slate-800 dark:group-hover:text-slate-200">
			{{ $judge?->name ?? 'Removed Judge' }}
		</span>
		@if ($showBadge)
			<span class="inline-flex items-center text-[10px] leading-none font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-indigo-500 text-white ml-1">AI</span>
		@endif
	</div>
</div>
