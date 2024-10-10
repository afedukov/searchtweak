@props(['snapshot', 'showPosition' => true, 'imageClass' => ''])
@php
	/** @var \App\Models\SearchSnapshot $snapshot */
@endphp

<div class="block w-full p-6 bg-white border border-gray-200 rounded-lg shadow-md dark:bg-gray-700 dark:border-gray-700">

	<h5 class="mb-2 text-sm font-bold tracking-tight text-gray-900 dark:text-white">{{ $snapshot->name }}</h5>

	<div class="grid grid-cols-3 gap-4">

		@if ($snapshot->image)
			<div class="col-span-3 sm:col-span-1 flex items-center justify-center">
				<a href="{{ $snapshot->image }}" target="_blank">
					<img src="{{ $snapshot->image }}" alt="{{ $snapshot->name }}" class="{{ $imageClass }}" />
				</a>
			</div>
		@endif

		<div class="col-span-3 pl-3 @if($snapshot->image) sm:col-span-2 @else sm:col-span-3 @endif">
			<ul class="text-xs text-gray-500 dark:text-gray-400 list-disc">
				@if ($showPosition)
					<li>
						<div class="flex gap-1.5">
							<span class="font-medium">position:</span>
							<x-typography.round-badge-blue size="sm" :value="$snapshot->position" />
						</div>
					</li>
				@endif
				<li>
					<div class="flex gap-1">
						<span class="font-medium">id:</span> {{ $snapshot->doc_id }}
					</div>
				</li>
				@foreach ($snapshot->doc as $key => $value)
					<li>
						<div class="flex gap-1">
							<span class="font-medium">{{ $key }}:</span>

							<x-evaluations.snapshot-preview-value :value="$value" />
						</div>
					</li>
				@endforeach
			</ul>
		</div>

	</div>
</div>
