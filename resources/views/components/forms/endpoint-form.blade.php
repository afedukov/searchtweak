<form wire:submit="saveEndpoint" id="endpoint-form">
	<div class="px-4 py-5 bg-white dark:bg-slate-800 sm:p-6">

		<!-- Endpoint Name -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-required for="endpointForm.name" value="Name" />
			<x-input type="text" wire:model="endpointForm.name" />
			<x-input-error for="endpointForm.name" />
		</div>

		<!-- Endpoint Description -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-optional for="endpointForm.description" value="Description" />
			<x-form.input.textarea rows="2" placeholder="Provide a description ..." wire:model="endpointForm.description"></x-form.input.textarea>
			<x-input-error for="endpointForm.description" />
		</div>

		<!-- Endpoint Method & URL -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-required for="endpointForm.url" value="URL" />

			<div class="flex">
				<!-- Method -->
				<select wire:model="endpointForm.method" class="text-right font-mono font-bold text-indigo-500 dark:text-indigo-300 min-w-24 rounded-lg rounded-r-none mt-1 block p-2.5 text-sm bg-gray-200 border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-600 dark:placeholder-gray-400 dark:focus:ring-blue-500 dark:focus:border-blue-500">
					@foreach (\App\Models\SearchEndpoint::VALID_METHODS as $method)
						<option value="{{ $method }}">
							{{ $method }}
						</option>
					@endforeach
				</select>

				<!-- URL -->
				<div class="w-full">
					<x-input wire:model="endpointForm.url" type="text" placeholder="https://example.org/search" class="rounded-l-none" />
				</div>
			</div>

			<x-input-error for="endpointForm.method" />
			<x-input-error for="endpointForm.url" />
			<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
				{{ __('Please provide the method and URL of the endpoint.') }}
			</p>

		</div>

		<!-- Mapper -->
		<div class="mb-8 last:mb-0">
			<div class="flex items-center">
				<x-form.label.label-required for="endpointForm.mapper_code" value="Mapper Code" />

				<!-- Mapper code tooltip -->
				<x-forms.parts.mapper-code-tooltip />
			</div>

			<x-forms.parts.mapper-code form="endpointForm" />
		</div>

		<!-- Custom Headers -->
		<div class="mb-4 last:mb-0" x-data="{ open: $wire.endpointForm.headers !== '' }" id="input-group-{{ md5(mt_rand()) }}">

			<div class="flex items-center cursor-pointer gap-2" @click.prevent="open = !open">
				<svg class="w-2 h-2 shrink-0 mb-1" :class="open ? 'rotate-180': 'rotate-90'" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
					<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
				</svg>
				<x-form.label.label-optional for="endpointForm.headers" class="cursor-pointer" value="Custom Headers" />
			</div>

			<div class="ml-4" x-show="open" x-cloak>
				<x-form.input.textarea class="font-mono text-gray-400" rows="2" placeholder="Provide custom headers ..." wire:model="endpointForm.headers"></x-form.input.textarea>
				<x-input-error for="endpointForm.headers" />

				<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
					Please provide the custom headers you would like to send with the request. Each header should be on a new line in the format
					<x-typography.inline-code>header: value</x-typography.inline-code>
				</p>
			</div>
		</div>

		<!-- Advanced Settings -->
		<div class="mb-8 last:mb-0" x-data="{ open: $persist(false).as('endpoint-advanced-settings-expanded') }" id="input-group-{{ md5(mt_rand()) }}">

			<div class="flex items-center cursor-pointer gap-2" @click.prevent="open = !open">
				<svg class="w-2 h-2 shrink-0 mb-1" :class="open ? 'rotate-180': 'rotate-90'" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
					<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
				</svg>
				<x-form.label.label-optional for="endpointForm.settings" class="cursor-pointer" value="Advanced Settings" />
			</div>

			<div class="ml-4 p-4 rounded-lg bg-gray-100 dark:bg-gray-800" x-show="open" x-cloak>

				<!-- Multi-Threading -->
				<div class="mb-4 last:mb-0">
					<x-form.label.label for="endpointForm.setting_mt" value="Multi-Threading" />
					<x-form.radio.radio-cards cols="2" class="mb-2">
						<x-form.radio.radio-cards-item
								id="endpointForm.setting-mt-auto"
								key="0"
								name="Auto"
								description="Allows multiple requests to be processed simultaneously."
								wire:model="endpointForm.setting_mt"
						>
						</x-form.radio.radio-cards-item>
						<x-form.radio.radio-cards-item
								id="endpointForm.setting-mt-single"
								key="1"
								name="Single"
								description="Processes requests one at a time."
								wire:model="endpointForm.setting_mt"
						>
						</x-form.radio.radio-cards-item>
					</x-form.radio.radio-cards>
					<label for="endpointForm.setting_mt" class="text-sm text-gray-500 dark:text-gray-400">
						If yoo prefer to not overload the endpoint, you can choose <span class="font-semibold">Single</span> option. This will help to prevent the endpoint from being overwhelmed by too many requests at once.
					</label>
				</div>

			</div>
		</div>

		<!-- Other Form Errors -->
		<x-input-error for="endpointForm.setting_mt" />

	</div>
</form>
