<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

	<!-- Dashboard actions -->
	<div class="flex flex-wrap justify-between items-center gap-3 mb-8">

		<!-- Left Column -->
		<div class="flex flex-wrap gap-3"
			<!-- Header -->
			<h2 class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
				{{ __('Dashboard') }}
			</h2>
		</div>

		<!-- Right Column -->
		<div class="flex flex-wrap gap-2">

			<!-- Widgets button -->
			<x-dropdown-widgets align="right" :widgets="$widgets" />

		</div>

	</div>

	<!-- Widgets -->
	<div class="grid grid-cols-12 gap-6">
		@php
			$visibleWidgets = array_filter($widgets, fn (array $widget) => $widget[\App\Models\UserWidget::FIELD_VISIBLE]);
		@endphp

		@forelse ($visibleWidgets as $widget)
			@livewire($widget[\App\Models\UserWidget::FIELD_WIDGET_CLASS], ['widget' => $widget], key($widget[\App\Models\UserWidget::FIELD_ID]))
		@empty
			<div>
				<div class="">
					{{ __('No widgets') }}
				</div>
			</div>
		@endforelse

	</div>

</div>
