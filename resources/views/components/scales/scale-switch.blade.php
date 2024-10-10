@props(['snapshot', 'scale', 'selected' => null])
@php
	/** @var \App\Services\Scorers\Scales\Scale $scale */
	/** @var \App\Models\SearchSnapshot $snapshot */
@endphp

@foreach ($scale->getValues() as $key => $value)
	<x-dynamic-component :component="$scale->getScaleSwitchComponent()"
			scale-key="{{ $key }}"
		 	selected="{{ $selected === $key }}"
			class="btn block px-3 py-1 text-white font-medium rounded-lg text-sm focus:outline-none"
			wire:click="grade('{{ $snapshot->id }}', '{{ $key }}')"
			wire:loading.attr="disabled"
	>
		{{ $key }}
	</x-dynamic-component>
@endforeach
