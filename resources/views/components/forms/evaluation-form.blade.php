@props(['models', 'fixed' => false])
@php
	$scorers = \App\Services\Scorers\ScorerFactory::getScorers();
    $scales = \App\Services\Scorers\Scales\ScaleFactory::getScales();

    $guidelinesSpinner = <<<HTML
<div class="flex items-center justify-center" role="status">
    <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
    </svg>
    <span class="sr-only">Loading...</span>
</div>
HTML;
@endphp

<form wire:submit="saveEvaluation" id="evaluation-form">
	<div
			class="px-4 py-5 bg-white dark:bg-slate-800 sm:p-6"
			x-data="{ models: {{ Js::from($models) }}, locked: $wire.evaluationForm.keywords.length > 0 }"
			x-init="
				$watch('$wire.evaluationForm.model_id', value => {
					const model = models.find(model => model.id == value);
					if (model) {
						if ($wire.evaluationForm.cloneModelTags) {
							$wire.evaluationForm.tags = model.tags;
							$wire.evaluationForm.keywords = model.settings?.keywords?.join('\n') || '';
						}

						locked = $wire.evaluationForm.keywords.length > 0;
					} else {
						$wire.evaluationForm.keywords = '';
						locked = false;
					}
				});
			"
	>

		<!-- Evaluation Name -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-required for="evaluationForm.name" value="Name" />
			<x-input type="text" wire:model="evaluationForm.name" />
			<x-input-error for="evaluationForm.name" />
		</div>

		<!-- Evaluation Description -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-optional for="evaluationForm.description" value="Description" />
			<x-form.input.textarea rows="2" placeholder="Provide a description ..." wire:model="evaluationForm.description"></x-form.input.textarea>
			<x-input-error for="evaluationForm.description" />
		</div>

		<!-- Evaluation Model -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-required for="evaluationForm.model_id" value="Model" />

			<select @if ($fixed) disabled @endif wire:model="evaluationForm.model_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 disabled:cursor-not-allowed">
				<option selected>Choose model</option>
				@foreach ($models as $model)
					<option value="{{ $model->id }}">{{ $model->name }}</option>
				@endforeach
			</select>

			<x-input-error for="evaluationForm.model_id" />
		</div>

		<!-- Evaluation Metrics -->
		<div
				class="mb-8 last:mb-0"
				x-data="{
					scorers: @js($scorers),
					metrics: $wire.entangle('evaluationForm.metrics'),
					showSettings: false,
					chooseScorer: false,
					current: null,
					num_results: 10
				}"
		>
			<x-form.label.label-required for="evaluationForm.metrics" value="Metrics" />

			<div class="flex flex-wrap gap-2 p-4 rounded-lg bg-gray-100 dark:bg-gray-800">

				<!-- Add Metric -->
				<div class="inline-block">
					<!-- Add Metric Button -->
					<button class="border-2 border-dashed bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded-lg p-4 flex items-center justify-center cursor-pointer" @click.prevent="chooseScorer = true">
						<svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
						</svg>
						<span class="ml-2 text-sm text-gray-500 dark:text-gray-400">Add Metric</span>
					</button>

					<!-- Choose Scorer Modal -->
					<x-modals.dialog-modal-alpine var="chooseScorer" max-width="xl" x-cloak id="evaluationForm-choose-scorer">
						<x-slot name="title">
							{{ __('Choose Scorer') }}
						</x-slot>

						<x-slot name="content">
							<ul class="p-2 grid w-full gap-4 md:grid-cols-3">
								@foreach ($scorers as $scorer)
									<label
											@click="
													metrics.push({
														scorer_type: '{{ $scorer->getType() }}',
														num_results: 10,
														settings: {}
													});
													chooseScorer = false;"
											class="cursor-pointer text-sm inline-flex items-center justify-between w-full p-5 text-gray-500 bg-white border border-gray-200 rounded-lg dark:hover:text-gray-300 dark:peer-checked:text-blue-500 peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600"
									>
										<div>
											<div class="w-full font-semibold text-gray-500 dark:text-gray-300 whitespace-nowrap">{{ $scorer->getDisplayName() }}</div>
											<div class="w-full text-xs text-gray-500 dark:text-gray-300">{{ $scorer->getBriefDescription() }}</div>
											<x-metrics.scale-type :scaleType="$scorer->getScale()->getType()" :scaleName="$scorer->getScale()->getName()" />
										</div>
									</label>
								@endforeach
							</ul>
						</x-slot>

						<x-slot name="footer">
							<x-secondary-button @click.prevent="chooseScorer = false">
								{{ __('Close') }}
							</x-secondary-button>
						</x-slot>
					</x-modals.dialog-modal-alpine>
				</div>

				<!-- Metrics List -->
				<template x-for="(metric, index) in metrics" :key="index">
					<div
							@click.prevent="
								if (!event.target.closest('.remove-metric-button')) {
									showSettings = !showSettings;
									current = metric;
									num_results = metric.num_results;
								}
							"
							class="p-4 rounded-lg border hover:bg-gray-50 dark:hover:bg-gray-600 border-gray-300 bg-white dark:bg-gray-700 dark:border-gray-600 cursor-pointer"
					>
						<div class="w-full inline-flex gap-2.5 justify-between items-start">
							<div>
								<div x-text="scorers[metric.scorer_type].name.replace('%d', metric.num_results)" class="font-semibold text-gray-500 dark:text-gray-300 whitespace-nowrap"></div>
								<div x-text="scorers[metric.scorer_type].brief_description" class="text-xs text-gray-500 dark:text-gray-300"></div>
							</div>

							<!-- Remove Metric -->
							<button @click.prevent="metrics.splice(index, 1)" class="remove-metric-button rounded-full p-1 text-slate-500 hover:text-slate-500 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-slate-300">
								<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
								</svg>
							</button>
						</div>

						<div>
							<template x-if="scorers[metric.scorer_type].scale.type === @js(\App\Services\Scorers\Scales\BinaryScale::SCALE_TYPE)">
								<span x-text="scorers[metric.scorer_type].scale.name" class="bg-blue-100 text-blue-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300"></span>
							</template>
							<template x-if="scorers[metric.scorer_type].scale.type === @js(\App\Services\Scorers\Scales\GradedScale::SCALE_TYPE)">
								<span x-text="scorers[metric.scorer_type].scale.name" class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300"></span>
							</template>
							<template x-if="scorers[metric.scorer_type].scale.type === @js(\App\Services\Scorers\Scales\DetailScale::SCALE_TYPE)">
								<span x-text="scorers[metric.scorer_type].scale.name" class="bg-purple-100 text-purple-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-purple-900 dark:text-purple-300"></span>
							</template>
						</div>
					</div>
				</template>
			</div>

			<x-input-error for="evaluationForm.metrics" />

			<!-- Metric Settings Modal -->
			<x-modals.dialog-modal-alpine var="showSettings" max-width="md" x-cloak id="evaluationForm-edit-metric-settings">
				<x-slot name="title">
					{{ __('Metric Settings') }}
				</x-slot>

				<x-slot name="content">
					<!-- Scorer -->
					<div class="p-4 rounded-lg border border-gray-300 dark:bg-gray-700 dark:border-gray-600">
						<div class="inline-flex gap-2.5 items-baseline">
							<div x-text="scorers[current?.scorer_type]?.name.replace('%d', num_results)" class="font-semibold text-gray-500 dark:text-gray-300"></div>
							<div x-text="scorers[current?.scorer_type]?.brief_description" class="text-sm text-gray-500 dark:text-gray-400"></div>
						</div>
						<div>
							<template x-if="scorers[current?.scorer_type]?.scale.type === @js(\App\Services\Scorers\Scales\BinaryScale::SCALE_TYPE)">
								<span x-text="scorers[current?.scorer_type]?.scale.name" class="bg-blue-100 text-blue-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300"></span>
							</template>
							<template x-if="scorers[current?.scorer_type]?.scale.type === @js(\App\Services\Scorers\Scales\GradedScale::SCALE_TYPE)">
								<span x-text="scorers[current?.scorer_type]?.scale.name" class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300"></span>
							</template>
							<template x-if="scorers[current?.scorer_type]?.scale.type === @js(\App\Services\Scorers\Scales\DetailScale::SCALE_TYPE)">
								<span x-text="scorers[current?.scorer_type]?.scale.name" class="bg-purple-100 text-purple-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-purple-900 dark:text-purple-300"></span>
							</template>
						</div>
					</div>

					<div x-text="scorers[current?.scorer_type]?.description" class="mt-6 text-sm text-gray-500 dark:text-gray-400"></div>
					<div class="mt-6">
						<x-form.label.label-required for="evaluationForm-quantity-input" value="Choose number of results" />
						<div class="relative flex items-center max-w-[8rem]">
							<x-typography.round-button-minus size="small" @click="if (num_results > 1) { num_results-- }" />
							<span x-text="num_results" class="text-gray-900 dark:text-white text-center w-8"></span>
							<x-typography.round-button-plus size="small" @click="if (num_results < 50) { num_results++ }" />
						</div>
						<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
							Please select a number from 1 to 50.
						</p>
					</div>
				</x-slot>

				<x-slot name="footer">
					<x-secondary-button @click.prevent="showSettings = false">
						{{ __('Close') }}
					</x-secondary-button>

					<x-button class="ms-3" @click.prevent="current.num_results = num_results; showSettings = false">
						{{ __('Save') }}
					</x-button>
				</x-slot>
			</x-modals.dialog-modal-alpine>
		</div>

		<!-- Evaluation Scale & Transformer -->
		<div
			class="mb-8 last:mb-0"
			x-data="{
				transformers: $wire.entangle('evaluationForm.transformers'),
				scaleType: $wire.entangle('evaluationForm.scale_type'),
				metrics: $wire.entangle('evaluationForm.metrics'),
				scorers: @js($scorers),
				guidelinesDiffer(value) {
					if (!$wire.evaluationForm.scale_type) {
						return false;
					}
					const defaultVal = $wire.evaluationForm.defaultGuidelines[$wire.evaluationForm.scale_type] || '';
					return value.trim() !== defaultVal.trim();
				},
				resetGuidelinesToDefaultVisible: false,
				get requiredTransformers() {
					const transformers = [];
					this.metrics.forEach(metric => {
						const metricScaleType = this.scorers[metric.scorer_type].scale.type || null;
						if (this.scaleType && metricScaleType !== this.scaleType) {
							const key = `${this.scaleType}_${metricScaleType}`;

							if (!transformers.some(t => t.key === key)) {
								transformers.push({
									key: key,
									from: {
										type: this.scaleType,
										label: document.getElementById('evaluation-scale-' + this.scaleType)?.innerHTML.trim(),
									},
									to: {
										type: metricScaleType,
										label: document.getElementById('evaluation-scale-' + metricScaleType)?.innerHTML.trim(),
									},
								});
							};
						}
					});
					return transformers;
				},
			}"
			x-init="
				resetGuidelinesToDefaultVisible = guidelinesDiffer($wire.evaluationForm.setting_scoring_guidelines);
				$watch('metrics', value => {
					let scaleType = null;

					for (const metric of value) {
						const metricScaleType = scorers[metric.scorer_type]?.scale?.type;

						if (!metric.search_evaluation_id && metricScaleType) {
							if (metricScaleType === 'detail') {
								scaleType = 'detail';
								break;
							} else if (metricScaleType === 'graded' && scaleType !== 'detail') {
								scaleType = 'graded';
							} else if (metricScaleType === 'binary' && !scaleType) {
								scaleType = 'binary';
							}
						}
					}

					if (scaleType) {
						this.scaleType = scaleType;
						$wire.evaluationForm.scale_type = scaleType;
					}
				});
				$watch('scaleType', value => {
					if (value) {
						$wire.evaluationForm.setting_scoring_guidelines = $wire.evaluationForm.guidelines[value] || '';
					}
				});
				$watch('$wire.evaluationForm.setting_scoring_guidelines', value => {
					resetGuidelinesToDefaultVisible = guidelinesDiffer(value);
					$wire.evaluationForm.guidelines[$wire.evaluationForm.scale_type] = value;
				});
			"
		>
			<div class="mb-8 last:mb-0">
				<x-form.label.label-required for="evaluationForm.scale_type" value="Scale" />

				<x-form.radio.radio-cards cols="3" class="mb-2">
					@foreach ($scales as $scale)
						<x-form.radio.radio-cards-item
								id="evaluationForm.scale-{{ $scale->getType() }}"
								key="{{ $scale->getType() }}"
								wire:model="evaluationForm.scale_type"
						>
							<div id="evaluation-scale-{{ $scale->getType() }}">
								<x-metrics.scale-type :scaleType="$scale->getType()" :scaleName="$scale->getName()" />
							</div>
							<div class="ml-2">
								@foreach ($scale->getGrades() as $grade)
									<x-dynamic-component :component="$scale->getScaleBadgeComponent()" :grade="$grade" size="sm" class="opacity-50" />
								@endforeach
							</div>
						</x-form.radio.radio-cards-item>
					@endforeach

				</x-form.radio.radio-cards>

				<x-input-error for="evaluationForm.scale_type" />
			</div>

			<div class="mb-8 last:mb-0" x-show="requiredTransformers.length > 0" x-cloak>
				<x-form.label.label-required for="evaluationForm.transformer" value="Transformers" />

				<div class="grid grid-cols-2 gap-4">

					<template x-for="transformer in requiredTransformers" :key="transformer.key">
						<div class="gap-2 p-4 rounded-lg bg-gray-100 dark:bg-gray-800 text-sm w-full text-gray-500 dark:text-gray-400">

							<div class="flex flex-wrap items-center mb-3">
								<!-- From scale type -->
								<div x-html="transformer.from.label"></div>

								<!-- Right Arrow -->
								<i class="fa-solid fa-arrow-right mr-2"></i>

								<!-- To scale type -->
								<div x-html="transformer.to.label"></div>
							</div>

							<x-form.input.textarea
									x-model="transformers[transformer.key]"
									rows="4"
									class="font-mono text-gray-400"
									placeholder="Transformer code ..."
							/>
						</div>
					</template>

				</div>

				<x-input-error for="evaluationForm.transformers" />
			</div>

			<!-- Scoring Guidelines -->
			<div class="mb-8 last:mb-0">
				<div class="flex gap-1 items-center">
					<x-form.label.label-optional for="evaluationForm.setting_scoring_guidelines" value="Scoring Guidelines" />
					<div class="inline-block mb-1">
						<x-tooltip-info>
							You can use <strong>Markdown</strong> syntax in this field. For more information, see the <a href="https://www.markdownguide.org/basic-syntax/" target="_blank" class="text-blue-600 dark:text-blue-500 hover:underline">Markdown Guide</a>.
						</x-tooltip-info>
					</div>
				</div>

				<x-form.input.textarea rows="4" placeholder="Provide scoring guidelines ..." wire:model="evaluationForm.setting_scoring_guidelines"></x-form.input.textarea>

				<!-- Preview Scoring Guidelines -->
				<div x-data="{ openPreview: false, guidelines: $wire.entangle('scoringGuidelinesPreviewHTML') }" class="relative">

					<!-- Links row -->
					<div class="inline-flex items-center gap-2 text-xs">
						<!-- Preview link -->
						<a
								href="#"
								@click.prevent="
									guidelines = {{ Js::from($guidelinesSpinner) }};
									$wire.previewScoringGuidelines();
									openPreview = !openPreview;
								"
								class="underline decoration-dotted hover:underline"
						>
							Preview
						</a>

						<!-- Reset to Default link -->
						<a href="#" x-show="resetGuidelinesToDefaultVisible" @click.prevent="$wire.evaluationForm.setting_scoring_guidelines = $wire.evaluationForm.defaultGuidelines[$wire.evaluationForm.scale_type]" class="underline decoration-dotted hover:underline">
							Reset to Default
						</a>
					</div>

					<!-- Preview Popover -->
					<div
							x-show="openPreview"
							@click.outside="openPreview = false"
							class="absolute z-60 w-full bg-white dark:bg-gray-700 border dark:border-gray-600 rounded-lg shadow-md"
					>
						<div
								class="scoring-guidelines p-5 space-y-4"
								wire:ignore
								x-html="guidelines"
						></div>
					</div>
				</div>

				<x-input-error for="evaluationForm.setting_scoring_guidelines" />
			</div>
		</div>

		<!-- Evaluation Keywords -->
		<div class="mb-8 last:mb-0">
			<div>
				<x-form.label.label-required for="evaluationForm.keywords" value="Keywords" class="inline-block mr-2" />
				<button @click.prevent="locked = !locked">
					<i class="fas text-gray-500 dark:text-gray-400" :class="locked ? 'fa-lock' : 'fa-unlock'"></i>
				</button>
			</div>

			<x-form.input.textarea class="disabled:bg-gray-100 disabled:dark:bg-gray-800" rows="4" placeholder="Provide keywords ..." wire:model="evaluationForm.keywords" x-bind:disabled="locked" />
			<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
				Please provide a list of keywords, one per line.
			</p>
			<x-input-error for="evaluationForm.keywords" />
		</div>

		<!-- Advanced Settings -->
		<div x-data="{ open: $persist(false).as('evaluation-advanced-settings-expanded') }" id="input-group-{{ unique_key() }}">

			<div class="flex items-center cursor-pointer gap-2" @click.prevent="open = !open">
				<svg class="w-2 h-2 shrink-0 mb-1" :class="open ? 'rotate-180': 'rotate-90'" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
					<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
				</svg>
				<x-form.label.label-optional for="evaluationForm.settings" class="cursor-pointer" value="Advanced Settings" />
			</div>

			<div class="ml-4 p-4 rounded-lg bg-gray-100 dark:bg-gray-800" x-show="open" x-cloak>

				<!-- Feedback Strategy -->
				<div class="mb-4 last:mb-0">
					<x-form.label.label for="evaluationForm.setting_feedback_strategy" value="Feedback Strategy" />
					<x-form.radio.radio-cards cols="2">
							<x-form.radio.radio-cards-item
									id="evaluationForm.setting-feedback-strategy-1"
									key="1"
									name="Single"
									description="Only one feedback is needed for each query/document pair. Provides quick results but may sacrifice quality."
									wire:model="evaluationForm.setting_feedback_strategy"
							>
							</x-form.radio.radio-cards-item>
							<x-form.radio.radio-cards-item
									id="evaluationForm.setting-feedback-strategy-3"
									key="3"
									name="Multiple"
									description="Allows for up to three feedbacks per query/document pair, resulting in higher quality assessments albeit requiring more effort."
									wire:model="evaluationForm.setting_feedback_strategy"
							>
							</x-form.radio.radio-cards-item>
					</x-form.radio.radio-cards>
				</div>

				<!-- Show Position -->
				<div class="mb-4 last:mb-0">
					<x-form.label.label for="evaluationForm.setting_show_position" value="Show Position" />

					<div class="flex items-start gap-3">
						<x-checkbox id="evaluationForm.setting_show_position" wire:model="evaluationForm.setting_show_position" class="mt-1" />
						<label for="evaluationForm.setting_show_position" class="text-sm text-gray-500 dark:text-gray-400">
							Normally, you wouldn't want to reveal the position or rank of a document returned in the search results to the Evaluator.
							However, if you're comparing against a pre-existing search engine results page, this information may be necessary.
						</label>
					</div>
				</div>

				<!-- Auto-Restart -->
				<div class="mb-4 last:mb-0">
					<x-form.label.label for="evaluationForm.setting_auto_restart" value="Auto-Restart" />

					<div class="flex items-start gap-3">
						<x-checkbox id="evaluationForm.setting_auto_restart" wire:model="evaluationForm.setting_auto_restart" class="mt-1" />
						<label for="evaluationForm.setting_auto_restart" class="text-sm text-gray-500 dark:text-gray-400">
							Automatically create and start a new evaluation with the same settings when the current evaluation is completed.
						</label>
					</div>
				</div>

				<!-- Re-use Grades Strategy -->
				<div class="mb-4 last:mb-0">
					<x-form.label.label for="evaluationForm.setting_show_position" value="Re-use Grades Strategy" />

					<x-form.radio.radio-cards cols="3" class="mb-2">
						<x-form.radio.radio-cards-item
								id="evaluationForm.setting-reuse-strategy-0"
								key="0"
								name="No"
								wire:model="evaluationForm.setting_reuse_strategy"
						>
						</x-form.radio.radio-cards-item>
						<x-form.radio.radio-cards-item
								id="evaluationForm.setting-reuse-strategy-1"
								key="1"
								name="Query/Doc"
								description="Re-use the grade for the same query/document pair."
								wire:model="evaluationForm.setting_reuse_strategy"
						>
						</x-form.radio.radio-cards-item>
						<x-form.radio.radio-cards-item
								id="evaluationForm.setting-reuse-strategy-2"
								key="2"
								name="Query/Doc/Position"
								description="Re-use the grade for the same query/document pair and position."
								wire:model="evaluationForm.setting_reuse_strategy"
						>
						</x-form.radio.radio-cards-item>
					</x-form.radio.radio-cards>
					<label for="evaluationForm.setting_reuse_strategy" class="text-sm text-gray-500 dark:text-gray-400">
						This option allows for consistent grading by re-using the same evaluation for identical query/document pairs or query/document/position combinations. It helps streamline the grading process, ensures uniformity in repeated assessments, and significantly speeds up the overall evaluation workflow. Cannot be combined with Auto-Restart.
					</label>
				</div>

				<!-- Tags -->
				<div class="mb-4 last:mb-0">
					<livewire:tags.manage-tags
							id="evaluationForm-manage-tags"
							wire:model="evaluationForm.tags"
							wire:key="{{ unique_key() }}"
							tooltip="Only users with the following tags can evaluate this search evaluation."
					/>
				</div>

			</div>
		</div>

		<!-- Other Form Errors -->
		<x-input-error for="evaluationForm.status" />
		<x-input-error for="evaluationForm.setting_feedback_strategy" />
		<x-input-error for="evaluationForm.setting_show_position" />
		<x-input-error for="evaluationForm.setting_auto_restart" />
		<x-input-error for="evaluationForm.setting_reuse_strategy" />
		<x-input-error for="evaluationForm.tags" />
		<x-input-error for="evaluationForm.tags.*" />
		<x-input-error for="evaluationForm.evaluation" />

	</div>

	@vite('resources/css/scoring-guidelines.css')
</form>
