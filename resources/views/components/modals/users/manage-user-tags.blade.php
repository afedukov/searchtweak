@props(['team'])

<x-modals.dialog-modal-alpine var="showUserTags" max-width="xl" x-cloak>
	<x-slot name="title">
		{{ __('Tags') }}
	</x-slot>

	<x-slot name="content">
		<x-tags.tags-edit :team="$team" />
	</x-slot>

	<x-slot name="footer">
		<x-secondary-button @click.prevent="showUserTags = false">
			{{ __('Close') }}
		</x-secondary-button>

		<x-button class="ms-3" @click.prevent="showUserTags = false" wire:click="saveUserTags" wire:loading.attr="disabled">
			{{ __('Save') }}
		</x-button>
	</x-slot>
</x-modals.dialog-modal-alpine>
