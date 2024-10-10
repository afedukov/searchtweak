@props(['id' => md5(mt_rand())])

<div>
	<button data-popover-target="tooltip-{{ $id }}" {{ $attributes->merge(['class' => 'block']) }}>
		<svg class="w-4 h-4 fill-current text-slate-400 dark:text-slate-500" viewBox="0 0 16 16">
			<path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 12c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1zm1-3H7V4h2v5z"/>
		</svg>
	</button>

	<x-tooltip id="tooltip-{{ $id }}" with-arrow>
		{{ $slot }}
	</x-tooltip>
</div>
