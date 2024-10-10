<div class="relative flex" x-data="{ open: false }">
	<button
			class="relative btn bg-white dark:bg-slate-800 border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600 text-slate-500 hover:text-slate-600 dark:text-slate-400 dark:hover:text-slate-300"
			aria-haspopup="true"
			@click.prevent="open = !open"
			:aria-expanded="open"
	>
		<span class="sr-only">Filter</span><wbr>
		<svg class="w-4 h-4 fill-current" viewBox="0 0 16 16">
			<path d="M9 15H7a1 1 0 010-2h2a1 1 0 010 2zM11 11H5a1 1 0 010-2h6a1 1 0 010 2zM13 7H3a1 1 0 010-2h10a1 1 0 010 2zM15 3H1a1 1 0 010-2h14a1 1 0 010 2z" />
		</svg>

		<div class="inline text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase ml-2">Filter by tag</div>

		<!-- Filter applied badge -->
		@if ($tag)
			<x-block.filter-applied-badge />
		@endif
	</button>
	<div
			class="origin-top-right z-10 absolute top-full min-w-56 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 pt-1.5 rounded shadow-lg overflow-hidden mt-1 sm:left-auto sm:right-0"
			@click.outside="open = false"
			@keydown.escape.window="open = false"
			x-show="open"
			x-transition:enter="transition ease-out duration-200 transform"
			x-transition:enter-start="opacity-0 -translate-y-2"
			x-transition:enter-end="opacity-100 translate-y-0"
			x-transition:leave="transition ease-out duration-200"
			x-transition:leave-start="opacity-100"
			x-transition:leave-end="opacity-0"
			x-cloak
	>
		<div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase pt-1.5 pb-2 px-3">Filter</div>

		<!-- Tags -->
		<div class="flex flex-wrap gap-2 px-3 pb-3">
			@forelse ($tags as $item)
				<x-tags.tag
						:color-class="$item->getColorClass()"
						@class([
        					'cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 focus:ring-offset-white dark:focus:ring-offset-slate-800 hover:ring-2 hover:ring-offset-2 hover:ring-blue-500 hover:ring-offset-white dark:hover:ring-offset-slate-800',
        					'ring-2 ring-offset-2 ring-blue-500 ring-offset-white dark:ring-offset-slate-800' => $item->id == $tag,
						])
						@click.prevent="
							$wire.set('tag', {{ Js::from($item->id) }});
							open = false;
						"
				>
					{{ $item->name }}
				</x-tags.tag>
			@empty
				<span class="text-xs text-gray-400 dark:text-gray-500">
					{{ __('No tags') }}
				</span>
			@endforelse
		</div>

		<div class="py-2 px-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/20">
			<ul class="flex items-center justify-between">
				<li>
					<button @click="$wire.tag = 0; open = false" class="btn-xs bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 text-slate-500 dark:text-slate-300 hover:text-slate-600 dark:hover:text-slate-200">
						{{ __('Reset') }}
					</button>
				</li>
			</ul>
		</div>
	</div>
</div>
