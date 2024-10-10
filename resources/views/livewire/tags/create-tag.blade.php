<div x-data="{ showCreateTag: @entangle('showCreateTag') }">
	<!-- Button -->
	<button
			class="text-xs px-2.5 py-1 border-2 border-dotted bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center cursor-pointer focus:outline-none"
			@click.prevent="showCreateTag = !showCreateTag"
	>
		<svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
			<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
		</svg>
		Create Tag
	</button>

	<!-- Popup -->
	<template x-teleport="#create-tag-{{ $id }}">
		<div x-show="showCreateTag">
			<div class="rounded-lg p-4 bg-gray-50 dark:bg-gray-800">

				<div class="flex gap-2 items-center">
					<div class="inline-flex">
						<!-- Color Picker -->
						<x-color-picker :colors="$colors" wire:model="color" />
					</div>

					<div class="inline-flex w-full">
						<x-input class="text-xs h-[36px]" wire:model="tagName" placeholder="Tag Name (optional)" />
					</div>

					<div class="inline-flex">
						<x-button class="text-xs mt-1" wire:click.prevent="createTag" wire:loading.attr="disabled">
							Create Tag
						</x-button>
					</div>
				</div>

				<!-- Error -->
				@if ($error)
					<div class="text-xs text-red-500 dark:text-red-400 mt-2">
						{{ $error }}
					</div>
				@endif

			</div>
		</div>
	</template>
</div>
