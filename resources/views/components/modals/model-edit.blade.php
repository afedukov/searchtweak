@props(['create' => false, 'fixed' => false, 'endpoints', 'executionResult'])

<x-dialog-modal wire:model.live="editModelModal">
	<x-slot name="title">
		@if ($create)
			{{ __('Create Model') }}
		@else
			{{ __('Edit Model') }}
		@endif
	</x-slot>

	<x-slot name="content">
		<x-forms.model-form :endpoints="$endpoints" :execution-result="$executionResult" :fixed="$fixed" :create="$create" />
	</x-slot>

	<x-slot name="footer">
		<x-secondary-button wire:click="$toggle('editModelModal')" wire:loading.attr="disabled">
			{{ __('Close') }}
		</x-secondary-button>

		<x-button class="ms-3" type="submit" form="model-form" wire:loading.attr="disabled">
			{{ __('Save') }}
		</x-button>
	</x-slot>
</x-dialog-modal>
