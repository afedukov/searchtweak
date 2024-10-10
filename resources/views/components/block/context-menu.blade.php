@props(['id'])

<div class="hidden has-[li]:inline-flex">
	<button
			id="{{ $id }}"
			data-dropdown-toggle="dropdown-{{ $id }}"
			data-dropdown-placement="bottom"
			data-dropdown-offset-skidding="-50"
			class="rounded-full text-slate-500 hover:text-slate-500 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-300"
			type="button"
	>
		<svg class="w-8 h-8 fill-current inline" aria-hidden="true" viewBox="0 0 32 32">
			<circle cx="16" cy="16" r="2" />
			<circle cx="10" cy="16" r="2" />
			<circle cx="22" cy="16" r="2" />
		</svg>
	</button>

	<!-- Dropdown menu -->
	<div id="dropdown-{{ $id }}" class="hidden z-60 min-w-36 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 py-1.5 rounded shadow-lg mt-1">
		<ul aria-labelledby="{{ $id }}">
			{{ $slot }}
		</ul>
	</div>
</div>
