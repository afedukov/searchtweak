@props(['entityId' => 0, 'tags', 'canManage' => false, 'emptyLabel' => 'No tags'])
@php
	/** @var \Illuminate\Database\Eloquent\Collection<\App\Models\Tag> $tags */
@endphp

<div {{ $attributes }}>

	<div class="flex items-center gap-1 flex-wrap">
		@forelse ($tags as $tag)
			@if ($canManage)
				<x-tags.tag
						:color-class="$tag->getColorClass()"
						class="cursor-pointer"
						@click.prevent="
							$wire.entityId = {{ $entityId }};
							tags = {{ Js::from($tags) }};
							availableTags = teamTags.filter(t => !tags.find(tag => tag.id === t.id));
							error = '';
							showUserTags = true;
						"
				>
					{{ $tag->name }}
				</x-tags.tag>
			@else
				<x-tags.tag :color-class="$tag->getColorClass()">
					{{ $tag->name }}
				</x-tags.tag>
			@endif
		@empty
			@if ($canManage)
				<button
						class="border-2 border-dotted bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded-lg p-0.5 flex items-center justify-center cursor-pointer"
						@click.prevent="
							$wire.entityId = {{ $entityId }};
							tags = {{ json_encode($tags) }};
							availableTags = teamTags.filter(t => !tags.find(tag => tag.id === t.id));
							error = '';
							showUserTags = true;
						"
				>
					<svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
					</svg>
				</button>
			@else
				<span class="text-xs text-gray-400 dark:text-gray-500">
					{{ $emptyLabel }}
				</span>
			@endif
		@endforelse
	</div>

</div>
