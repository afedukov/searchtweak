@props(['executionResult'])

<x-modals.simple-modal>
	<x-slot name="button">
		<a href="javascript:void(0)" @click.prevent="open = true" class="text-blue-600 hover:text-blue-700 dark:text-blue-500 dark:hover:text-blue-400 hover:underline">
			View
		</a>
	</x-slot>

	<!-- Content -->
	<div class="mb-3 last:mb-0">
		<div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase px-2 mb-2">Search Results</div>
		<ul class="text-sm">
			@foreach ($executionResult['documents'] as $doc)
				<li class="mb-2 last:mb-0">
					<a href="#" class="block w-full p-6 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700">
						<h5 class="mb-2 text-sm font-bold tracking-tight text-gray-900 dark:text-white">{{ $doc['id'] }}</h5>

						<ul class="ml-3 text-xs text-gray-500 dark:text-gray-400 list-disc">
							<li>
								<span class="font-medium">position</span>: {{ $doc['position'] }}
							</li>
							@foreach ($doc['attributes'] as $key => $value)
								<li>
									<span class="font-medium">{{ $key }}</span>: {{ $value }}
								</li>
							@endforeach
						</ul>
					</a>
				</li>
			@endforeach
		</ul>
	</div>
</x-modals.simple-modal>
