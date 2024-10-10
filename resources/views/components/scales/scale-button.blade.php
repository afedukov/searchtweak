@props(['feedback', 'scale'])
@php
	/** @var \App\Services\Scorers\Scales\Scale $scale */
	/** @var \App\Models\UserFeedback $feedback */
@endphp

@foreach ($scale->getValues() as $key => $value)
	<x-dynamic-component :component="$scale->getScaleButtonComponent()"
			scale-key="{{ $key }}"
			class="px-3 sm:px-5 py-2.5 btn font-mono block text-white font-semibold rounded-lg text-xs sm:text-sm focus:outline-none disabled:opacity-25 transition"
			wire:click="grade('{{ $feedback->id }}', '{{ $key }}')"
			wire:loading.attr="disabled"
	>
		{{ $value }}
	</x-dynamic-component>
@endforeach
