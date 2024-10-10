@props(['evaluation'])

<div class="inline-flex gap-3 items-start px-4 py-3 rounded-lg border border-gray-300 bg-white dark:bg-gray-700 dark:border-gray-600 max-w-sm">
	<div>
		<svg class="shrink-0 w-5 h-5 text-gray-400 mt-0.5" viewBox="0 0 24 24">
			<path class="fill-current text-indigo-300" d="M13 6.068a6.035 6.035 0 0 1 4.932 4.933H24c-.486-5.846-5.154-10.515-11-11v6.067Z" />
			<path class="fill-current text-indigo-500" d="M18.007 13c-.474 2.833-2.919 5-5.864 5a5.888 5.888 0 0 1-3.694-1.304L4 20.731C6.131 22.752 8.992 24 12.143 24c6.232 0 11.35-4.851 11.857-11h-5.993Z" />
			<path class="fill-current text-indigo-600" d="M6.939 15.007A5.861 5.861 0 0 1 6 11.829c0-2.937 2.167-5.376 5-5.85V0C4.85.507 0 5.614 0 11.83c0 2.695.922 5.174 2.456 7.17l4.483-3.993Z" />
		</svg>
	</div>
	<div>
		<div class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
			{{ $evaluation->name }}
		</div>
		@if ($evaluation->description)
			<div class="text-xs text-gray-400 dark:text-gray-400">
				{{ $evaluation->description }}
			</div>
		@endif
	</div>
</div>
