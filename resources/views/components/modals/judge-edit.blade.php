@props(['create' => false])

<x-dialog-modal wire:model.live="editJudgeModal">
	<x-slot name="title">
		@if ($create)
			{{ __('Create Judge') }}
		@else
			{{ __('Edit Judge') }}
		@endif
	</x-slot>

	<x-slot name="content">
		<x-forms.judge-form :is-editing="!$create" />
	</x-slot>

	<x-slot name="footer">
		<x-secondary-button wire:click="$toggle('editJudgeModal')" wire:loading.attr="disabled">
			{{ __('Close') }}
		</x-secondary-button>

		<x-button class="ms-3" type="submit" form="judge-form" wire:loading.attr="disabled">
			{{ __('Save') }}
		</x-button>
	</x-slot>
</x-dialog-modal>
