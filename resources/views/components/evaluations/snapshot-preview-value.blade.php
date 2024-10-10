@props(['value'])

@if (is_string($value) && \App\Services\Helpers::isUrl($value))
	<a href="{{ $value }}" class="text-blue-600 dark:text-blue-500 hover:underline line-clamp-1" target="_blank">
		{{ $value }}
	</a>
@else
	@if (is_array($value))
		<div>
			@foreach ($value as $v)
				<span class="inline-block bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 mb-1 rounded-full dark:bg-blue-900 dark:text-blue-300 whitespace-nowrap">{{ $v }}</span>
			@endforeach
		</div>
	@else
		<span>{{ $value }}</span>
	@endif
@endif
