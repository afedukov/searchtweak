<div class="flex gap-3 mb-3 sm:mb-0" x-data="{ finish: @entangle('confirmingEvaluationFinish') }">
	@if (!$evaluation->isFinished() && $evaluation->hasStarted() && Gate::check('finish', $evaluation))
		<!-- Finish Button -->
		<x-button
				@class([
					'relative flex',
					'bg-red-500 hover:bg-red-600' => $evaluation->progress < 70,
				])
				@click.prevent="finish = true"
		>
			<i class="fa-solid fa-check"></i>
			<span class="ml-2">
				{{ __('Finish Evaluation') }}
			</span>
		</x-button>

		<!-- Finish Evaluation Confirmation Modal -->
		<x-modals.confirmation-modal-alpine var="finish" x-cloak id="finish-evaluation-confirmation">
			<x-slot name="title">
				{{ __('Finish Evaluation') }}
			</x-slot>

			<x-slot name="content">
				{{ __('Are you sure you want to finish this evaluation? Once finished, you will not be able to make any changes.') }}
			</x-slot>

			<x-slot name="footer">
				<x-secondary-button @click.prevent="finish = false">
					{{ __('Cancel') }}
				</x-secondary-button>

				<x-button
						wire:click="finishEvaluation"
						wire:loading.attr="disabled"
						@class([
							'ms-3',
							'bg-red-500 hover:bg-red-600' => $evaluation->progress < 70,
						])
				>
					{{ __('Finish') }}
				</x-button>
			</x-slot>
		</x-modals.confirmation-modal-alpine>
	@endif
</div>
