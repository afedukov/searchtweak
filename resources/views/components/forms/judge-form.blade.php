@props(['isEditing' => false])

<form wire:submit="saveJudge" id="judge-form">
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
						{{ ucfirst($provider) }}
					</option>
				@endforeach
			</select>
			<x-input-error for="judgeForm.provider" />
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
						<x-input type="number" min="0" max="20" wire:model="judgeForm.setting_batch_size" class="!w-24" />
						<span class="text-sm text-gray-500 dark:text-gray-400">
							0 = Auto (all pairs for keyword)
						</span>
					</div>
					<x-input-error for="judgeForm.setting_batch_size" />
					<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
						{{ __('Number of query/product pairs to send in a single LLM request (0-20).') }}
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
