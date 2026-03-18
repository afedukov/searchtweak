<x-dialog-modal wire:model.live="createUserModal">
	<x-slot name="title">
		{{ __('Create User') }}
	</x-slot>

	<x-slot name="content">
		<x-forms.user-form />
	</x-slot>

	<x-slot name="footer">
		<x-secondary-button wire:click="$toggle('createUserModal')" wire:loading.attr="disabled">
			{{ __('Cancel') }}
		</x-secondary-button>

		<x-button class="ms-3" type="submit" form="user-form" wire:loading.attr="disabled">
			{{ __('Create') }}
		</x-button>
	</x-slot>
</x-dialog-modal>
