@props(['id' => md5(mt_rand()), 'padding' => 'px-5 py-4'])

<div class="xl:col-span-6 col-span-full bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
	<header class="{{ $padding }} border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
		<!-- Widget title -->
		<div class="flex items-center pt-1">
			<h2 class="font-semibold text-slate-800 dark:text-slate-100">
				{{ $title }}
			</h2>
			@isset($tooltip)
				<!-- Widget tooltip -->
				<x-tooltip-info class="ml-2">
					{{ $tooltip }}
				</x-tooltip-info>
			@endisset
		</div>

		<!-- Menu button -->
		<x-block.widget-menu-button :removable="$this->removable" />
	</header>
	<div class="p-3">
		<!-- Widget content -->
		{{ $slot }}
	</div>
</div>
