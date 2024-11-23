@props(['endpoints', 'executionResult', 'fixed' => false, 'create' => false])
@php
    $bodyTypes = \App\Services\Models\RequestHeadersService::getBodyTypes();
@endphp

<form
		wire:submit="saveModel"
		id="model-form"
		x-data="{ endpoints: @js($endpoints), currentEndpointName: '' }"
		x-init="
			$watch('$wire.modelForm.endpoint_id', value => {
				if ($wire.modelForm) {
					$wire.modelForm.mapper_code = endpoints[value]?.mapper_code || '';
					currentEndpointName = endpoints[value]?.name || '';
				}
			});
			$watch('$wire.modelForm.mapper_code', value => {
				if (endpoints[$wire.modelForm.endpoint_id]) {
					endpoints[$wire.modelForm.endpoint_id].mapper_code = value;
				}
			});
		"
>
	<div class="px-4 py-5 bg-white dark:bg-slate-800 sm:p-6">

		<!-- Model Name -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-required for="modelForm.name" value="Name" />
			<x-input type="text" wire:model="modelForm.name" />
			<x-input-error for="modelForm.name" />
		</div>

		<!-- Model Description -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-optional for="modelForm.description" value="Description" />
			<x-form.input.textarea rows="2" placeholder="Provide a description ..." wire:model="modelForm.description"></x-form.input.textarea>
			<x-input-error for="modelForm.description" />
		</div>

		<!-- Model Endpoint -->
		<div class="mb-4 last:mb-0">
			<x-form.label.label-required for="modelForm.endpoint_id" value="Endpoint" />

			<select
					@if ($fixed) disabled @endif
					wire:model="modelForm.endpoint_id"
					class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 disabled:cursor-not-allowed"
			>
				<option value="" @selected($create)>Choose endpoint</option>
				@foreach ($endpoints as $endpoint)
					@if (!$create || $endpoint->isActive())
						<option value="{{ $endpoint->id }}" :selected="$wire.modelForm.endpoint_id == @js($endpoint->id)">{{ $endpoint->name }}</option>
					@endif
				@endforeach
			</select>

			<x-input-error for="modelForm.endpoint_id" />
		</div>

		<!-- Endpoint Mapper Code -->
		<div class="mb-4 last:mb-0 p-4 rounded-lg bg-gray-50 dark:bg-gray-800" x-data="{ open: $persist(false).as('mapper-code-expanded') }" id="input-group-{{ unique_key() }}">

			<div class="flex items-center cursor-pointer gap-2" @click.prevent="open = !open">
				<svg class="w-2 h-2 shrink-0 mb-1" :class="open ? 'rotate-180': 'rotate-90'" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
					<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
				</svg>

				<x-form.label.label-required for="modelForm.mapper_code" class="cursor-pointer">
					Mapper Code
					[<span x-text="currentEndpointName" class="font-bold"></span>]
				</x-form.label.label-required>

				<!-- Mapper code tooltip -->
				<x-forms.parts.mapper-code-tooltip class="ml-0" />
			</div>

			<div class="ml-4" x-show="open" x-cloak>
				<x-forms.parts.mapper-code form="modelForm" />
			</div>
		</div>

		<!-- Model Params & Body Tabs -->
		<div class="mb-8 last:mb-0" x-data="{
			modelFormTab: 'params',
			activeClasses: 'inline-block p-4 border-b-2 rounded-t-lg text-blue-600 hover:text-blue-600 dark:text-blue-500 dark:hover:text-blue-500 border-blue-600 dark:border-blue-500',
            inactiveClasses: 'inline-block p-4 border-b-2 rounded-t-lg text-gray-600 hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-400 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-500'
		}">
			<div class="mb-4 border-b border-gray-200 dark:border-gray-700">
				<ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="modelForm-default-tab" role="tablist">
					<li role="presentation">
						<button :class="modelFormTab == 'params' ? activeClasses : inactiveClasses" id="modelForm-params-tab" type="button" @click="modelFormTab = 'params'">Query Parameters</button>
					</li>
					<li role="presentation">
						<button :class="modelFormTab == 'body' ? activeClasses : inactiveClasses" id="modelForm-body-tab" type="button" @click="modelFormTab = 'body'">Request Body</button>
					</li>
				</ul>
			</div>
			<div id="modelForm-tab-content">
				<div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800" id="modelForm-params" x-show="modelFormTab == 'params'">

					<!-- Model Params -->
					<div class="col-span-6 sm:col-span-4">
						<x-form.input.textarea class="font-mono text-gray-400" rows="5" placeholder="Provide query parameters ..." wire:model="modelForm.params"></x-form.input.textarea>

						<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
							Please provide the query parameters you would like to send with the request.
							The query variable is represented by the <x-typography.inline-code>{{ \App\Services\Models\ExecuteModelService::TERM_QUERY }}</x-typography.inline-code> string.
							Any instance of this pattern will be substituted with the full query.
							Each parameter should be on a new line in the format
							<x-typography.inline-code>key: value</x-typography.inline-code>
						</p>
					</div>

				</div>
				<div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800" id="modelForm-body" x-show="modelFormTab == 'body'">

					<!-- Body Type -->
					<div class="mb-3">
						<select wire:model="modelForm.body_type" class="block p-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
							<option value="" selected>Choose type</option>
							@foreach ($bodyTypes as $type => $label)
								<option value="{{ $type }}">{{ $label }}</option>
							@endforeach
						</select>
					</div>

					<!-- Model Body -->
					<div class="col-span-6 sm:col-span-4">
						<x-form.input.textarea class="font-mono text-gray-400" rows="7" placeholder="Provide request body ..." wire:model="modelForm.body"></x-form.input.textarea>

						<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
							Please provide the request body you would like to send with the request.
							The query variable is represented by the <x-typography.inline-code>{{ \App\Services\Models\ExecuteModelService::TERM_QUERY }}</x-typography.inline-code> string.
							Any instance of this pattern will be substituted with the full query.
						</p>
					</div>

				</div>
			</div>

			<x-input-error for="modelForm.params" />
			<x-input-error for="modelForm.body_type" />
			<x-input-error for="modelForm.body" />
		</div>

		<!-- Test Model -->
		<div class="mb-8 last:mb-0">
			<div class="flex items-center">
				<x-form.label.label-optional for="test-model" value="Test Model" />

				<!-- Test Model tooltip -->
				<x-tooltip-info class="ml-2 mb-1">
					<div class="w-96">
						Temporarily replace <x-typography.inline-code class="text-xs">#query#</x-typography.inline-code>
						in the Query Parameters or Request Body with any search keyword that will return at least one document.
					</div>
				</x-tooltip-info>
			</div>

			<p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
				Test the model by sending a request to the endpoint and attempting to retrieve documents using the corresponding endpoint mapper code.
			</p>
			<div class="flex items-baseline gap-2" x-data="{ executionResult: $wire.entangle('executionResult') }">
				<x-button type="button" wire:click="test" wire:loading.attr="disabled" @click="executionResult = null">
					{{ __('Test') }}
				</x-button>
				<span wire:loading wire:target="test" class="text-sm font-medium text-gray-400 dark:text-gray-500">
					Requesting...
				</span>
				<template x-if="executionResult && executionResult.successful" x-cloak>
					<div x-data="{ open: false }" class="p-2 items-baseline text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
						[<span class="font-semibold" x-text="executionResult.code"></span>] <span x-text="executionResult.message"></span>

						<a data-popover-target="popover-execution-result" data-popover-trigger="click" href="#" class="text-blue-600 hover:text-blue-700 dark:text-blue-500 dark:hover:text-blue-400 underline decoration-dotted">
							View Response
						</a>

						<!-- Popover -->
						<div data-popover id="popover-execution-result" role="tooltip" class="w-full absolute z-10 invisible inline-block text-sm transition-opacity duration-300 opacity-0">
							<div class="block w-full p-6 bg-white border border-gray-200 rounded-lg shadow-md dark:bg-gray-700 dark:border-gray-700">

								<div class="inline-block mb-2 px-2.5 py-0.5 rounded text-sm tracking-tight bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
									[<span class="font-semibold" x-text="executionResult.code"></span>] <span x-text="executionResult.message"></span>
								</div>

								<x-form.input.textarea class="text-xs font-mono" rows="15" disabled x-text="executionResult.response" />
							</div>
						</div>

						<br />
						<span class="font-medium" x-text="executionResult.count"></span> <span x-text="executionResult.documents_plural"></span> successfully retrieved.

						@if ($executionResult && $executionResult['count'] > 0)
							<!-- View Button -->
							<div class="inline-block ml-3">
								<div class="flex items-center">
									<svg class="w-2 h-2 shrink-0 mr-1" :class="open ? 'rotate-180': 'rotate-90'" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
										<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
									</svg>
									<a href="javascript:void(0)" @click.prevent="open = !open" class="text-blue-600 hover:text-blue-700 dark:text-blue-500 dark:hover:text-blue-400 hover:underline">
										View
									</a>
								</div>
							</div>

							<!-- Search Results Content -->
							<div class="my-3 last:mb-0" x-show="open">
								<ul class="text-sm">
									@foreach ($executionResult['documents'] as $doc)
										<li class="mb-2 last:mb-0">

											@php
												$snapshot = \App\Models\SearchSnapshot::createFromDocument(
													\App\Services\Mapper\Document::createFromArray($doc)
												);
											@endphp

											<x-evaluations.snapshot-preview :snapshot="$snapshot" :showPosition="true"/>
										</li>
									@endforeach
								</ul>
							</div>
						@endif
					</div>
				</template>
				<template x-if="executionResult && !executionResult.successful" x-cloak>
					<div class="p-2 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 text-ellipsis overflow-hidden" role="alert">
						<template x-if="executionResult.code > 0">
							[<span class="font-semibold" x-text="executionResult.code"></span>]
						</template>
						<span x-text="executionResult.message"></span>
					</div>
				</template>
			</div>
		</div>

		<!-- Keywords -->
		<div class="mb-4 last:mb-0" x-data="{ open: $persist(false).as('model-keywords-expanded') }">

			<div class="flex items-center cursor-pointer gap-2" @click.prevent="open = !open">
				<svg class="w-2 h-2 shrink-0 mb-1" :class="open ? 'rotate-180': 'rotate-90'" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
					<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
				</svg>
				<x-form.label.label-optional for="modelForm.keywords" class="cursor-pointer" value="Keywords" />
			</div>

			<div class="ml-4" x-show="open" x-cloak>

				<x-form.input.textarea rows="4" placeholder="Provide keywords ..." wire:model="modelForm.keywords"></x-form.input.textarea>
				<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
					Please provide a list of keywords, one per line. These keywords will be used as the default set of keywords for every search evaluation created under this search model, and it will still be possible to change them later.
				</p>
				<x-input-error for="modelForm.keywords" />

			</div>
		</div>

		<!-- Custom Headers -->
		<div class="mb-4 last:mb-0" x-data="{ open: $wire.modelForm.headers !== '' }" id="input-group-{{ unique_key() }}">

			<div class="flex items-center cursor-pointer gap-2" @click.prevent="open = !open">
				<svg class="w-2 h-2 shrink-0 mb-1" :class="open ? 'rotate-180': 'rotate-90'" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
					<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
				</svg>
				<x-form.label.label-optional for="modelForm.headers" class="cursor-pointer" value="Custom Headers" />
			</div>

			<div class="ml-4" x-show="open" x-cloak>
				<x-form.input.textarea class="font-mono text-gray-400" rows="2" placeholder="Provide custom headers ..." wire:model="modelForm.headers"></x-form.input.textarea>
				<x-input-error for="modelForm.headers" />

				<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
					Please provide the custom headers you would like to send with the request.
					Provided headers will override the headers set in the endpoint configuration.
					Each header should be on a new line in the format
					<x-typography.inline-code>header: value</x-typography.inline-code>
				</p>
			</div>
		</div>

		<!-- Advanced Settings -->
		<div class="mb-8 last:mb-0" x-data="{ open: $persist(false).as('model-advanced-settings-expanded') }">

			<div class="flex items-center cursor-pointer gap-2" @click.prevent="open = !open">
				<svg class="w-2 h-2 shrink-0 mb-1" :class="open ? 'rotate-180': 'rotate-90'" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
					<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
				</svg>
				<x-form.label.label-optional for="modelForm.tags" class="cursor-pointer" value="Advanced Settings" />
			</div>

			<div class="ml-4 p-4 rounded-lg bg-gray-100 dark:bg-gray-800" x-show="open" x-cloak>

				<!-- Tags -->
				<div class="mb-4 last:mb-0">
					<livewire:tags.manage-tags
							id="modelForm-manage-tags"
							wire:model="modelForm.tags"
							key="{{ unique_key() }}"
							tooltip="Selected tags will be preselected for every new search evaluation created under this model."
					/>

					<x-input-error for="modelForm.tags" />
					<x-input-error for="modelForm.tags.*" />
				</div>

			</div>
		</div>

	</div>
</form>
