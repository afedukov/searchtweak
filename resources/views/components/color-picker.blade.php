@props(['colors'])

<div
		class="relative"
		x-data="{
			showColorPicker: false,
			color: $wire.entangle('{{ $attributes->wire('model')->value() }}'),
			colorClass: 'bg-blue-200 text-blue-800 dark:bg-blue-800 dark:text-blue-300'
		}"
>
	<button
			class="px-2.5 py-2 h-[36px] mt-1 rounded-md border items-center cursor-pointer bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600"
			@click.prevent="showColorPicker = !showColorPicker"
	>
		<div class="w-14 h-full border border-gray-300 dark:border-gray-600 rounded-sm" :class="colorClass"></div>
	</button>

	<div
			class="origin-top-right z-10 absolute bottom-full left-0 bg-white min-w-[200px] dark:bg-slate-800 border border-slate-200 dark:border-slate-700 py-1.5 rounded shadow-lg overflow-hidden"
			@click.outside="showColorPicker = false"
			@keydown.escape.window="showColorPicker = false"
			x-show="showColorPicker"
			x-transition:enter="transition ease-out duration-200 transform"
			x-transition:enter-start="opacity-0 -translate-y-2"
			x-transition:enter-end="opacity-100 translate-y-0"
			x-transition:leave="transition ease-out duration-200"
			x-transition:leave-start="opacity-100"
			x-transition:leave-end="opacity-0"
			x-cloak
	>
		<div class="grid grid-cols-4 gap-2 p-4">
			@foreach ($colors as $color => $colorClass)
				<div class="inline text-center">
					<button
							class="bg-{{ $color }}-500 w-6 h-6 rounded-full border border-gray-200 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 focus:ring-offset-white dark:focus:ring-offset-slate-800 hover:ring-2 hover:ring-offset-2 hover:ring-blue-500 hover:ring-offset-white dark:hover:ring-offset-slate-800"
							:class="{'ring-2 ring-offset-2 ring-blue-500 ring-offset-white dark:ring-offset-slate-800': color === '{{ $color }}' }"
							@click.prevent="showColorPicker = false; color = @js($color); colorClass = @js($colorClass)"
					></button>
				</div>
			@endforeach
		</div>
	</div>
</div>
