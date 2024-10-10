@props(['endpoint'])
@php
	/** @var \App\Models\SearchEndpoint $endpoint */

	$hasLink = Gate::check('update', $endpoint);
@endphp

@if ($hasLink)
	<a href="#" wire:click.prevent="editEndpoint('{{ $endpoint->id }}')" class="inline-block">
@endif

<div class="inline-flex gap-2.5 items-center px-2.5 py-2 rounded-lg border border-gray-250 bg-white dark:bg-gray-700 dark:border-gray-600 @if($hasLink) hover:shadow-md dark:hover:shadow-gray-900 transition-shadow ease-in-out @endif">
	<span class="text-sm font-medium px-2.5 py-0.5 rounded {{ $endpoint->getMethodBadgeClass() }}">
		{{ $endpoint->method }}
	</span>
	<span class="font-semibold text-gray-500 dark:text-gray-300">
		{{ $endpoint->name }}
	</span>
	@if ($endpoint->description)
		<x-tooltip-info>
			<div class="max-w-80">
				{{ $endpoint->description }}
			</div>
		</x-tooltip-info>
	@endif
</div>

@if ($hasLink)
	</a>
@endif
