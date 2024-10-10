<x-widget-layout>

	<x-slot name="title">
		Give Feedback
	</x-slot>

	<!-- Widget content -->
	<div class="overflow-x-auto">
		<div class="px-5 py-3">

			<!-- Feedback button -->
			<a href="{{ route('feedback') }}" class="inline-block">
				<div class="relative">
					<button @if($ungradedSnapshotsCount === 0) disabled @endif class="relative flex btn bg-indigo-500 hover:bg-indigo-600 text-white whitespace-nowrap disabled:hover:bg-indigo-500 disabled:opacity-50">
						<svg class="w-4 h-4 fill-current opacity-50 shrink-0" viewBox="0 0 16 16">
							<path d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1z" />
						</svg>
						<span class="ml-2 pr-2">
							{{ __('Feedback') }}
						</span>
						<!-- Non-graded Snapshots Count Badge -->
						@if ($ungradedSnapshotsCount > 0)
							<x-block.notification-badge :count="$ungradedSnapshotsCount" class="w-6 h-6" />
						@endif
					</button>
				</div>
			</a>

			<!-- Info icon and feedback description -->
			<div class="flex gap-3 mt-8">
				<i class="fa-regular fa-file-lines text-gray-500 dark:text-gray-400 text-lg mt-0.5"></i>

				<div class="text-sm text-gray-500 dark:text-gray-400">
					@if ($ungradedSnapshotsCount > 0)
						<p class="font-bold mb-2 text-slate-700 dark:text-slate-200">
							Your insights matter!
						</p>
						<p>
							Help us enhance the search experience by sharing your thoughts on the results you see.
							Your feedback drives improvements.
						</p>
					@else
						<p class="font-bold mb-2 text-slate-700 dark:text-slate-200">
							Thank you for your valuable contributions!
						</p>
						<p>
							Currently, there are no search results available for evaluation. Please check back later.
							Your dedication helps us improve the search experience for everyone!
						</p>
					@endif
				</div>
			</div>

		</div>
	</div>
</x-widget-layout>
