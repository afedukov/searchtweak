@props(['evaluation', 'keyword'])
@php /** @var \App\Models\EvaluationKeyword $keyword */ @endphp

<div>
	<table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
		@if ($keyword->total_count === 0)
			<tr class="border-b dark:border-gray-700">
				<td colspan="2" class="px-6 py-4 text-center">
					{{ __('Nothing found') }}
				</td>
			</tr>
		@else
			@forelse ($keyword->snapshots as $snapshot)
				<tr class="border-b dark:border-gray-700" wire:key="snapshot-{{ $snapshot->id }}">
					<td class="px-6 py-4 align-top">
						<!-- Grade Buttons -->
						<livewire:evaluations.evaluation-grade-buttons :evaluation="$evaluation" :snapshot="$snapshot" key="grade-buttons-{{ $snapshot->id }}" />
					</td>
					<td class="px-6 py-4">
						<x-evaluations.snapshot-preview :snapshot="$snapshot" :show-position="true" image-class="max-h-24" />
					</td>
				</tr>
			@empty
				<tr class="border-b dark:border-gray-700">
					<td colspan="2" class="px-6 py-4 text-center">
						@if ($keyword->failed)
							<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
								@if ($keyword->execution_message)
									{{ $keyword->execution_message }}
								@else
									{{ __('Failed to execute the keyword.') }}
								@endif
							</div>
						@else
							{{ __('No snapshots available. Please start the evaluation.') }}
						@endif
					</td>
				</tr>
			@endforelse
		@endif
	</table>
</div>
