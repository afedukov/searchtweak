@props(['model'])

<a href="{{ route('model', $model->id) }}" class="inline-block">
	<div class="inline-flex gap-2.5 items-center text-sm px-2.5 py-2 rounded-lg border border-gray-250 bg-white dark:bg-gray-700 dark:border-gray-600 min-w-36 hover:shadow-md dark:hover:shadow-gray-900 transition-shadow ease-in-out">
		<div>
			<svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24">
				<path class="fill-current text-indigo-300" d="M13 15l11-7L11.504.136a1 1 0 00-1.019.007L0 7l13 8z" />
				<path class="fill-current text-indigo-600" d="M13 15L0 7v9c0 .355.189.685.496.864L13 24v-9z" />
				<path class="fill-current text-indigo-500" d="M13 15.047V24l10.573-7.181A.999.999 0 0024 16V8l-11 7.047z" />
			</svg>
		</div>
		<span class="font-semibold text-gray-500 dark:text-gray-300">
			{{ $model->name }}
		</span>
		@if ($model->description)
			<x-tooltip-info>
				<div class="max-w-80">
					{{ $model->description }}
				</div>
			</x-tooltip-info>
		@endif
	</div>
</a>
