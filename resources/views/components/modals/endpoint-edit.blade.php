@props(['create' => false])

<x-dialog-modal wire:model.live="editEndpointModal">
	<x-slot name="title">
		@if ($create)
			{{ __('Create Endpoint') }}
		@else
			{{ __('Edit Endpoint') }}
		@endif
	</x-slot>

	<x-slot name="content">
		<x-forms.endpoint-form />
	</x-slot>

	<x-slot name="footer">
		<x-secondary-button wire:click="$toggle('editEndpointModal')" wire:loading.attr="disabled">
			{{ __('Close') }}
		</x-secondary-button>

		<x-button class="ms-3" type="submit" form="endpoint-form" wire:loading.attr="disabled">
			{{ __('Save') }}
		</x-button>
	</x-slot>
</x-dialog-modal>
