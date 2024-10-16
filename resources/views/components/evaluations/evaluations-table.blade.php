@props(['evaluations', 'withModel' => false, 'evaluationForm'])
@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator|\App\Models\SearchEvaluation[] $evaluations */
@endphp

<div x-data="{ confirmingEvaluationRemoval: @entangle('confirmingEvaluationRemoval'), evaluationIdBeingRemoved: @entangle('evaluationIdBeingRemoved') }">

<!-- Table -->
<table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
	<thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
	<tr>
		<th scope="col" class="px-5 py-3">
			{{ __('Evaluation') }}
		</th>
		@if ($withModel)
			<th scope="col" class="px-5 py-3">
				{{ __('Model') }}
			</th>
		@endif
		<th scope="col" class="px-5 py-3">
			{{ __('Metrics') }}
		</th>
		<th scope="col" class="px-5 py-3">
			{{ __('Progress') }}
		</th>
		<th scope="col" class="px-5 py-3 text-center">
			{{ __('Keywords') }}
		</th>
		<th scope="col" class="px-5 py-3">
			{{ __('Status') }}
		</th>
		<th scope="col" class="px-5 py-3">
			{{ __('Created') }}
		</th>
		<th scope="col" class="px-5 py-3 text-right">
			{{ __('Action') }}
		</th>
	</tr>
	</thead>
	<tbody>
	@forelse ($evaluations as $evaluation)
		<tr wire:key="evaluation-item-{{ $evaluation->id }}" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
			<th scope="row" class="px-5 py-4 font-medium text-gray-900 dark:text-white align-baseline">
				<div class="max-w-64 min-w-28">
					<a href="{{ route('evaluation', $evaluation->id) }}">
						<div>
							{{ $evaluation->name }}
						</div>
						<div class="text-sm text-gray-400 dark:text-gray-400">
							{{ $evaluation->description }}
						</div>
					</a>
				</div>

				<x-tags.tags-list :tags="$evaluation->tags" empty-label="" class="mt-1" />
			</th>
			@if ($withModel)
				<td class="px-5 py-4 align-baseline">
					<!-- Evaluation Model -->
					<x-evaluations.evaluation-model :model="$evaluation->model" />
				</td>
			@endif
			<td class="px-5 py-4 align-baseline">
				<!-- Evaluation Metrics -->
				<div class="flex flex-wrap gap-3">
					@foreach ($evaluation->metrics as $metric)
						<x-metrics.evaluation-metric :metric="$metric" :keywords-count="$evaluation->keywords_count" />
					@endforeach
				</div>
			</td>
			<td class="px-5 py-4 align-baseline">
				<livewire:evaluations.evaluation-progress link :evaluation="$evaluation" wire:key="{{ md5(mt_rand()) }}" />
			</td>
			<td class="px-5 py-4 align-baseline text-center">
				<livewire:evaluations.evaluation-keywords-count :evaluation="$evaluation" wire:key="{{ md5(mt_rand()) }}" />
			</td>
			<td class="px-5 py-4 align-baseline">
				<livewire:evaluations.evaluation-status :evaluation="$evaluation" wire:key="{{ md5(mt_rand()) }}" />
			</td>
			<td class="px-5 py-4 align-baseline">
				<!-- Evaluation Control -->
				<livewire:evaluations.evaluation-control :evaluation="$evaluation" wire:key="{{ md5(mt_rand()) }}" />
			</td>
			<td class="px-5 py-4 text-right align-baseline">
				<!-- Context Menu -->
				<x-block.context-menu id="context-{{ $evaluation->id }}">
					<!-- View Evaluation -->
					@if (Gate::check('view', $evaluation))
						<x-block.context-menu-item href="{{ route('evaluation', $evaluation->id) }}">
							{{ __('View') }}
						</x-block.context-menu-item>
					@endif

					<!-- View User Feedback -->
					@if (Gate::check('viewFeedback', $evaluation))
						<x-block.context-menu-item href="{{ route('evaluation.feedback', $evaluation->id) }}">
							{{ __('User Feedback') }}
						</x-block.context-menu-item>
					@endif

					<!-- Give Feedback -->
					@if (Gate::check('giveFeedbackEvaluationPool', $evaluation))
						<x-block.context-menu-item href="{{ route('feedback', $evaluation->id) }}">
							{{ __('Give Feedback') }}
						</x-block.context-menu-item>
					@endif

					<!-- Edit Evaluation -->
					@if ($evaluation->isPending() && Gate::check('update', $evaluation))
						<x-block.context-menu-item wire:click="editEvaluation('{{ $evaluation->id }}')">
							{{ __('Edit') }}
						</x-block.context-menu-item>
					@endif

					<!-- Export Evaluation -->
					@if ($evaluation->isFinished() && Gate::check('export', $evaluation))
						<x-block.context-menu-item wire:click="exportEvaluation('{{ $evaluation->id }}')">
							{{ __('Export') }}
						</x-block.context-menu-item>
					@endif

					<!-- Clone Evaluation -->
					@if (Gate::check('create-evaluation', Auth::user()->currentTeam))
						<x-block.context-menu-item wire:click="cloneEvaluation('{{ $evaluation->id }}')">
							{{ __('Clone') }}
						</x-block.context-menu-item>
					@endif

					<!-- Delete Evaluation -->
					@if ($evaluation->isDeletable() && Gate::check('delete', $evaluation))
						<x-block.context-menu-item
								@click="
									evaluationIdBeingRemoved = {{ $evaluation->id }};
									confirmingEvaluationRemoval = true;
									FlowbiteInstances.getInstance('Dropdown', 'dropdown-context-{{ $evaluation->id }}').hide();
								"
								class="text-rose-500"
						>
							{{ __('Delete') }}
						</x-block.context-menu-item>
					@endif
				</x-block.context-menu>
			</td>
		</tr>
	@empty
		<tr>
			<td class="px-5 py-4 text-center" colspan="{{ $withModel ? 8 : 7 }}">
				<span class="text-gray-400 dark:text-gray-500">
					{{ __('No evaluations found') }}
				</span>
			</td>
		</tr>
	@endforelse
	</tbody>
</table>

<!-- Navigation -->
<nav class="items-center flex-column flex-wrap md:flex-row justify-between pt-4" aria-label="Table navigation">
	{{ $evaluations->links() }}
</nav>

<!-- Modals -->

<!-- Delete Evaluation Confirmation Modal -->
<x-modals.confirmation-modal-alpine var="confirmingEvaluationRemoval" x-cloak>
	<x-slot name="title">
		{{ __('Delete Evaluation') }}
	</x-slot>

	<x-slot name="content">
		{{ __('Are you sure you would like to delete this evaluation? Once the evaluation is deleted, all of its resources and data will be permanently deleted.') }}
	</x-slot>

	<x-slot name="footer">
		<x-secondary-button @click.prevent="confirmingEvaluationRemoval = false" wire:loading.attr="disabled">
			{{ __('Cancel') }}
		</x-secondary-button>

		<x-danger-button class="ms-3" wire:click="deleteEvaluation" wire:loading.attr="disabled">
			{{ __('Delete') }}
		</x-danger-button>
	</x-slot>
</x-modals.confirmation-modal-alpine>

</div>
