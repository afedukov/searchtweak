@props(['id' => null, 'team', 'tooltip' => ''])
@php
	$id ??= md5(mt_rand());
	$colors = \App\Models\Tag::getColors();
@endphp

<div>
	<span class="text-sm font-medium mb-1 flex gap-1 items-center">
		Assigned Tags <span class="text-xs text-gray-400 dark:text-gray-500">(Click tag to remove)</span>
		@if ($tooltip)
			<div class="inline-block">
				<x-tooltip-info>
					{{ $tooltip }}
				</x-tooltip-info>
			</div>
		@endif
	</span>

	<!-- User Tags -->
	<div class="flex flex-wrap rounded-lg border border-dashed p-4 gap-2 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-500 mb-4">
		<template x-if="tags.length === 0">
			<span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded text-gray-400 dark:text-gray-500">
				No tags
			</span>
		</template>
		<template x-for="tag in tags" :key="tag.id">
			<span
					:class="tag.color_class"
					class="min-h-[26px] inline-flex items-center px-2 py-1 text-xs font-medium rounded cursor-pointer"
					@click.prevent="tags = tags.filter(t => t.id !== tag.id); availableTags.push(tag);"
			>
				<span x-text="tag.name" class="min-w-6 text-center"></span>
			</span>
		</template>
	</div>

	<span class="block text-sm font-medium mb-1">
		Available Tags <span class="text-xs text-gray-400 dark:text-gray-500">(Click tag to add)</span>
	</span>

	<!-- Available Tags -->
	<div class="flex flex-wrap rounded-lg border border-dashed p-4 gap-2 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-500 items-center mb-4">

		<livewire:tags.create-tag :id="$id" :team="$team" :colors="$colors" wire:model="tag" wire:key="{{ md5(mt_rand()) }}" />

		<template x-if="availableTags.length === 0">
			<span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded text-gray-400 dark:text-gray-500">
				No available tags
			</span>
		</template>
		<template x-for="tag in availableTags" :key="tag.id">
			<span
					:class="tag.color_class"
					class="min-h-[26px] inline-flex items-center px-2 py-1 text-xs font-medium rounded cursor-pointer"
					@click.prevent="
						if (!event.target.closest('.delete-tag-button') && !tags.find(t => t.id === tag.id)) {
							tags.push(tag); availableTags = availableTags.filter(t => t.id !== tag.id);
						}
					"
			>
				<span x-text="tag.name" class="min-w-6 text-center"></span>

				<button
						type="button"
						class="delete-tag-button inline-flex items-center p-1 ms-2 text-sm text-gray-400 bg-transparent rounded-sm hover:bg-blue-200 hover:text-blue-900 dark:hover:bg-gray-800 dark:hover:text-gray-300 focus:outline-none"
						aria-label="Remove"
						@click.prevent="
							showDeleteTag = true;
							tagToDelete = tag.id;
						"
				>
					<svg class="w-2 h-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
						<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
					</svg>
					<span class="sr-only">Remove tag</span>
				</button>
			</span>
		</template>

	</div>

	<!-- Error -->
	<template x-if="$wire.error">
		<div class="text-xs text-red-500 dark:text-red-400 mt-2" x-text="$wire.error"></div>
	</template>

	<!-- Create Tag Popup Container -->
	<div id="create-tag-{{ $id }}"></div>

	<!-- Delete tag Confirmation Modal -->
	<x-modals.confirmation-modal-alpine var="showDeleteTag" maxWidth="sm" x-cloak>
		<x-slot name="title">
			{{ __('Delete Tag') }}
		</x-slot>

		<x-slot name="content">
			{{ __('Are you sure you want to permanently delete this tag?') }}
		</x-slot>

		<x-slot name="footer">
			<x-secondary-button @click.prevent="showDeleteTag = false">
				{{ __('Cancel') }}
			</x-secondary-button>

			<x-button wire:click.prevent="deleteTag" wire:loading.attr="disabled" class="bg-red-500 hover:bg-red-600 ms-3">
				{{ __('Delete') }}
			</x-button>
		</x-slot>
	</x-modals.confirmation-modal-alpine>
</div>
