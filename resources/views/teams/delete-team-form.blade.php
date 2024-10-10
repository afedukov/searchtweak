<div class="col-span-6 sm:col-span-4 mt-8">
	<label for="delete-team" class="block text-sm font-medium mb-1">
		{{ __('Delete Team') }}
	</label>

	<div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
		{{ __('Once a team is deleted, all of its resources and data will be permanently deleted. Before deleting this team, please download any data or information regarding this team that you wish to retain.') }}
	</div>

	<div class="mt-5">
		<x-danger-button wire:click="$toggle('confirmingTeamDeletion')" wire:loading.attr="disabled">
			{{ __('Delete Team') }}
		</x-danger-button>
	</div>

	<!-- Delete Team Confirmation Modal -->
	<x-confirmation-modal wire:model.live="confirmingTeamDeletion">
		<x-slot name="title">
			{{ __('Delete Team') }}
		</x-slot>

		<x-slot name="content">
			{{ __('Are you sure you want to delete this team?') }}
		</x-slot>

		<x-slot name="footer">
			<x-secondary-button wire:click="$toggle('confirmingTeamDeletion')" wire:loading.attr="disabled">
				{{ __('Cancel') }}
			</x-secondary-button>

			<x-danger-button class="ms-3" wire:click="deleteTeam" wire:loading.attr="disabled">
				{{ __('Delete Team') }}
			</x-danger-button>
		</x-slot>
	</x-confirmation-modal>
</div>
