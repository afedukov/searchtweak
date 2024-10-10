@props(['create' => false, 'models', 'fixed' => false])

<x-dialog-modal wire:model.live="editEvaluationModal">
	<x-slot name="title">
		@if ($create)
			{{ __('Create Evaluation') }}
		@else
			{{ __('Edit Evaluation') }}
		@endif
	</x-slot>

	<x-slot name="content">
		<x-forms.evaluation-form :fixed="$fixed" :models="$models" />
	</x-slot>

	<x-slot name="footer">
		<x-secondary-button wire:click="$toggle('editEvaluationModal')" wire:loading.attr="disabled">
			{{ __('Close') }}
		</x-secondary-button>

		<x-button class="ms-3" type="submit" form="evaluation-form" wire:loading.attr="disabled">
			{{ __('Save') }}
		</x-button>
	</x-slot>
</x-dialog-modal>
