<div>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
			<div class="flex items-center gap-2">
				Give Feedback
			</div>
		</h2>
	</x-slot>

	<div>
		<!-- Navigation Tabs -->
		<x-block.navigation-tabs>
			<!-- Go Back -->
			<x-go-back href="{{ $evaluation ? route('evaluation', $evaluation->id) : route('dashboard') }}" />
		</x-block.navigation-tabs>

		<!-- Feedback -->
		<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
			<div class="p-12 col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">

				@if ($feedback === null)
					<div class="text-sm text-gray-500 dark:text-gray-400 text-center">
						<p class="font-bold text-lg mb-3 text-slate-700 dark:text-slate-200">
							Thank you for your valuable contributions!
						</p>
						<p>
							Currently, there are no search results available for evaluation. Please check back later.
							Your dedication helps us improve the search experience for everyone!
						</p>
					</div>
				@else
					@if ($scoringGuidelines)
						<div class="mb-4">
							<x-modals.simple-modal>
								<x-slot name="button">
									<a href="javascript:void(0)" @click.prevent="open = true" class="text-xs underline decoration-dotted">
										Scoring Guidelines
									</a>
								</x-slot>

								<div class="scoring-guidelines p-5 space-y-4">
									{!! $scoringGuidelines !!}
								</div>
							</x-modals.simple-modal>
						</div>
					@endif

					<div class="mb-8 flex items-center gap-3 justify-between">
						<div>
							<span class="font-medium text-lg text-gray-500 dark:text-gray-400">
								Keyword
							</span>
							<x-block.bordered-label class="font-bold text-lg text-gray-900 dark:text-white">
								{{ $feedback->snapshot->keyword->keyword }}
							</x-block.bordered-label>
						</div>
						<div>
							@if ($previous)
								<button
										class="btn gap-1 ml-2 px-3 py-1 font-medium rounded-lg text-xs text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
										wire:click="goPrevious"
										wire:loading.attr="disabled"
								>
									Previous
									<x-dynamic-component :component="$previous->snapshot->keyword->evaluation->getScale()->getScaleBadgeComponent()" :grade="$previous->grade" size="sm" />
								</button>
							@endif
						</div>
					</div>

					<div class="text-lg font-semibold text-gray-900 dark:text-white mb-8">
						{{ $feedback->snapshot->name }}
					</div>

					<div class="grid grid-cols-12 gap-6">

						@if ($feedback->snapshot->image)
							<div class="col-span-12 sm:col-span-4 md:col-span-3 flex items-center justify-center">
								<a href="{{ $feedback->snapshot->image }}" target="_blank">
									<img src="{{ $feedback->snapshot->image }}" alt="{{ $feedback->snapshot->name }}" />
								</a>
							</div>
						@endif

						<div class="col-span-12 pl-3 @if($feedback->snapshot->image) sm:col-span-8 md:col-span-9 @else sm:col-span-12 @endif">
							<ul class="text-gray-500 dark:text-gray-400 list-disc">
								@if ($feedback->snapshot->keyword->evaluation->showPosition())
									<li>
										<div class="flex gap-1.5">
											<span class="font-semibold">position:</span>
											<x-typography.round-badge-blue size="md" :value="$feedback->snapshot->position" />
										</div>
									</li>
								@endif
								<li>
									<div class="flex gap-1.5">
										<span class="font-semibold">id:</span>
										<span>{{ $feedback->snapshot->doc_id }}</span>
									</div>
								</li>
								@foreach ($feedback->snapshot->doc as $key => $value)
									<li>
										<div class="flex gap-1.5">
											<span class="font-semibold">{{ $key }}:</span>

											<x-evaluations.snapshot-preview-value :value="$value" />
										</div>
									</li>
								@endforeach
							</ul>
						</div>

					</div>

					<!-- Scale Buttons -->
					<div class="mt-8 flex flex-wrap justify-center gap-2">
						<x-scales.scale-button :feedback="$feedback" :scale="$feedback->snapshot->keyword->evaluation->getScale()" />
					</div>
				@endif

			</div>
		</div>
	</div>

	@vite('resources/css/scoring-guidelines.css')
</div>
