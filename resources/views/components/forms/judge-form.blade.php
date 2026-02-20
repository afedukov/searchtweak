@props(['isEditing' => false])

<form wire:submit="saveJudge" id="judge-form" x-data="{ provider: $wire.entangle('judgeForm.provider') }">
	<div class="px-4 py-5 bg-white dark:bg-slate-800 sm:p-6">

		<!-- Judge Name -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-required for="judgeForm.name" value="Name" />
			<x-input type="text" wire:model="judgeForm.name" />
			<x-input-error for="judgeForm.name" />
		</div>

		<!-- Judge Description -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-optional for="judgeForm.description" value="Description" />
			<x-form.input.textarea rows="2" placeholder="Provide a description ..." wire:model="judgeForm.description"></x-form.input.textarea>
			<x-input-error for="judgeForm.description" />
		</div>

		<!-- Provider -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-required for="judgeForm.provider" value="Provider" />
			<select wire:model="judgeForm.provider" class="w-full rounded-lg mt-1 block p-2.5 text-sm bg-white border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
				@foreach (\App\Models\Judge::VALID_PROVIDERS as $provider)
					<option value="{{ $provider }}">
						{{ \App\Models\Judge::getProviderLabel($provider) }}
					</option>
				@endforeach
			</select>
			<x-input-error for="judgeForm.provider" />
		</div>

		<!-- Base URL -->
		<div class="mb-8 last:mb-0" x-show="provider === '{{ \App\Models\Judge::PROVIDER_CUSTOM_OPENAI }}' || provider === '{{ \App\Models\Judge::PROVIDER_OLLAMA }}'" x-cloak>
			<x-form.label.label-optional for="judgeForm.setting_base_url" value="Base URL" />
			<x-input type="text" wire:model="judgeForm.setting_base_url" placeholder="https://api.example.com/v1" />
			<x-input-error for="judgeForm.setting_base_url" />
			<p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-show="provider === '{{ \App\Models\Judge::PROVIDER_CUSTOM_OPENAI }}'">
				{{ __('OpenAI-compatible API base URL. Example: https://api.openai.com/v1') }}
			</p>
			<p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-show="provider === '{{ \App\Models\Judge::PROVIDER_OLLAMA }}'">
				{{ __('Optional. Defaults to http://localhost:11434/v1') }}
			</p>
		</div>

		<!-- Model Name -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-required for="judgeForm.model_name" value="Model Name" />
			<x-input type="text" wire:model="judgeForm.model_name" placeholder="e.g. gpt-4, claude-sonnet-4-5-20250929, gemini-pro" />
			<x-input-error for="judgeForm.model_name" />
			<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
				{{ __('The model identifier as specified by the provider.') }}
			</p>
		</div>

		<!-- API Key -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-required for="judgeForm.api_key" value="API Key" />
			<x-input type="password" wire:model="judgeForm.api_key" placeholder="{{ $isEditing ? '••••••••  (leave blank to keep current)' : '' }}" autocomplete="off" />
			<x-input-error for="judgeForm.api_key" />
			@if ($isEditing)
				<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
					{{ __('Leave blank to keep the current API key.') }}
				</p>
			@endif
		</div>

		<!-- Prompt (tabbed by scale) -->
		<div class="mb-8 last:mb-0" x-data="{
			promptTab: 'binary',
			activeClasses: 'inline-block p-4 border-b-2 rounded-t-lg text-blue-600 hover:text-blue-600 dark:text-blue-500 dark:hover:text-blue-500 border-blue-600 dark:border-blue-500',
			inactiveClasses: 'inline-block p-4 border-b-2 rounded-t-lg text-gray-600 hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-400 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-500'
		}">
			<x-form.label.label-required for="judgeForm.prompt" value="Prompt" />
			<div class="mb-4 border-b border-gray-200 dark:border-gray-700">
				<ul class="flex flex-wrap -mb-px text-sm font-medium text-center" role="tablist">
					<li role="presentation">
						<button :class="promptTab == 'binary' ? activeClasses : inactiveClasses" type="button" @click="promptTab = 'binary'">Binary</button>
					</li>
					<li role="presentation">
						<button :class="promptTab == 'graded' ? activeClasses : inactiveClasses" type="button" @click="promptTab = 'graded'">Graded</button>
					</li>
					<li role="presentation">
						<button :class="promptTab == 'detail' ? activeClasses : inactiveClasses" type="button" @click="promptTab = 'detail'">Detail</button>
					</li>
				</ul>
			</div>
			<div>
				<div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800" x-show="promptTab == 'binary'">
					<x-form.input.textarea class="font-mono text-sm" rows="10" placeholder="Enter the binary scale evaluation prompt ..." wire:model="judgeForm.prompt_binary"></x-form.input.textarea>
				</div>
				<div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800" x-show="promptTab == 'graded'">
					<x-form.input.textarea class="font-mono text-sm" rows="10" placeholder="Enter the graded scale evaluation prompt ..." wire:model="judgeForm.prompt_graded"></x-form.input.textarea>
				</div>
				<div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800" x-show="promptTab == 'detail'">
					<x-form.input.textarea class="font-mono text-sm" rows="10" placeholder="Enter the detail scale evaluation prompt ..." wire:model="judgeForm.prompt_detail"></x-form.input.textarea>
				</div>
			</div>
			<x-input-error for="judgeForm.prompt_binary" />
			<x-input-error for="judgeForm.prompt_graded" />
			<x-input-error for="judgeForm.prompt_detail" />
			<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
				{{ __('The prompt template for each grading scale. Use the') }}
				<x-typography.inline-code>#pairs#</x-typography.inline-code>
				{{ __('placeholder for the JSON array of query/product pairs to evaluate.') }}
			</p>

			<!-- Test Judge -->
			<div class="mt-6">
				<x-form.label.label-optional for="test-judge" value="Test Judge" />
				<p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
					{{ __('Send sample query/product pairs to the LLM using the active prompt tab to verify the configuration.') }}
				</p>
				<div class="flex items-baseline gap-2" x-data="{ judgeTestResult: $wire.entangle('judgeTestResult') }">
					<x-button type="button" wire:click="testJudge" wire:loading.attr="disabled"
						@click="judgeTestResult = null; $wire.judgeTestScaleType = promptTab">
						{{ __('Test') }}
					</x-button>
					<span wire:loading wire:target="testJudge" class="text-sm font-medium text-gray-400 dark:text-gray-500">
						{{ __('Requesting...') }}
					</span>

					{{-- Success --}}
					<template x-if="judgeTestResult && judgeTestResult.successful" x-cloak>
						<div x-data="{ open: false }" class="p-2 items-baseline text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
							{{ __('LLM responded.') }}
							{{ __('Graded') }} <span class="font-semibold" x-text="judgeTestResult.graded_count"></span>/<span x-text="judgeTestResult.pairs_count"></span> {{ __('pairs.') }}

							{{-- Toggle details --}}
							<div class="inline-block ml-2">
								<div class="flex items-center">
									<svg class="w-2 h-2 shrink-0 mr-1" :class="open ? 'rotate-180': 'rotate-90'" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
										<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
									</svg>
									<a href="javascript:void(0)" @click.prevent="open = !open" class="text-blue-600 hover:text-blue-700 dark:text-blue-500 dark:hover:text-blue-400 hover:underline">
										{{ __('View Details') }}
									</a>
								</div>
							</div>

							{{-- Grade details --}}
							<div class="mt-3" x-show="open" x-cloak>
								<template x-for="grade in judgeTestResult.grades" :key="grade.pair_index">
									<div class="mb-2 p-2 rounded bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
										<div class="flex items-center gap-2 mb-1">
											<span class="inline-block px-2 py-0.5 text-xs font-semibold rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300" x-text="'Grade: ' + grade.grade"></span>
											<span class="text-xs text-gray-500 dark:text-gray-400" x-text="grade.product"></span>
										</div>
										<template x-if="grade.reason">
											<p class="text-xs text-gray-600 dark:text-gray-300" x-text="grade.reason"></p>
										</template>
									</div>
								</template>

								{{-- Raw response --}}
								<div class="mt-2">
									<p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('Raw Response:') }}</p>
									<x-form.input.textarea class="text-xs font-mono" rows="8" disabled x-text="judgeTestResult.response" />
								</div>
							</div>
						</div>
					</template>

					{{-- Error --}}
					<template x-if="judgeTestResult && !judgeTestResult.successful" x-cloak>
						<div class="p-2 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 text-ellipsis overflow-hidden" role="alert">
							<span x-text="judgeTestResult.error"></span>
						</div>
					</template>
				</div>
			</div>

		</div>

		<!-- Model Parameters -->
		<div class="mb-4 last:mb-0" x-data="{ open: $wire.judgeForm.model_params !== '' }" id="input-group-{{ unique_key() }}">

			<div class="flex items-center cursor-pointer gap-2" @click.prevent="open = !open">
				<svg class="w-2 h-2 shrink-0 mb-1" :class="open ? 'rotate-180': 'rotate-90'" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
					<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
				</svg>
				<x-form.label.label-optional for="judgeForm.model_params" class="cursor-pointer" value="Model Parameters" />
			</div>

			<div class="ml-4" x-show="open" x-cloak>
				<x-form.input.textarea class="font-mono text-gray-400" rows="4" placeholder="temperature: 0.1&#10;max_tokens: 2048" wire:model="judgeForm.model_params"></x-form.input.textarea>
				<x-input-error for="judgeForm.model_params" />

				<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
					{{ __('Extra parameters passed directly to the model API. Each parameter on a new line in the format') }}
					<x-typography.inline-code>param: value</x-typography.inline-code>.
				</p>
			</div>
		</div>

		<!-- Advanced Settings -->
		<div class="mb-8 last:mb-0" x-data="{ open: $persist(false).as('judge-advanced-settings-expanded') }" id="input-group-{{ unique_key() }}">

			<div class="flex items-center cursor-pointer gap-2" @click.prevent="open = !open">
				<svg class="w-2 h-2 shrink-0 mb-1" :class="open ? 'rotate-180': 'rotate-90'" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
					<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
				</svg>
				<x-form.label.label-optional for="judgeForm.settings" class="cursor-pointer" value="Advanced Settings" />
			</div>

			<div class="ml-4 p-4 rounded-lg bg-gray-100 dark:bg-gray-800" x-show="open" x-cloak>

				<!-- Batch Size -->
				<div class="mb-4 last:mb-0">
					<x-form.label.label-optional for="judgeForm.setting_batch_size" value="Batch Size" />
					<div class="flex items-center gap-3">
						<x-input type="number" min="1" max="20" wire:model="judgeForm.setting_batch_size" class="!w-24" />
					</div>
					<x-input-error for="judgeForm.setting_batch_size" />
					<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
						{{ __('Number of query/product pairs to send in a single LLM request (1-20).') }}
					</p>
				</div>

				<!-- Tags -->
				<div class="mb-4 last:mb-0">
					<livewire:tags.manage-tags
							id="judgeForm-manage-tags"
							wire:model="judgeForm.tags"
							key="{{ unique_key() }}"
					/>

					<x-input-error for="judgeForm.tags" />
					<x-input-error for="judgeForm.tags.*" />
				</div>

			</div>
		</div>

	</div>
</form>
