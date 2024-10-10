@props(['selectedUser' => null, 'message', 'sendTeamMessageTo' => '', 'recipients' => []])

<div class="px-4 py-5 bg-white dark:bg-slate-800 sm:p-6 gap-4">

	<!-- Recipient -->
	@if ($selectedUser)
		<div class="col-span-6 sm:col-span-4">
			<x-block.user-name :user="$selectedUser" />
		</div>
	@else
		<x-form.radio.radio-cards cols="3">
			@foreach ($recipients as $recipient)
				<x-form.radio.radio-cards-item
					id="send-team-message-to-{{ $recipient['key'] }}"
					key="{{ $recipient['key'] }}"
					name="{{ $recipient['name'] }}"
					description="{{ $recipient['description'] }}"
					disabled="{{ $recipient['total'] === 0 }}"
					wire:model="sendTeamMessageTo"
				>
					<div class="w-full text-xs text-gray-400 dark:text-gray-500">
						Total users: {{ $recipient['total'] }}
					</div>
				</x-form.radio.radio-cards-item>
			@endforeach
		</x-form.radio.radio-cards>
		<x-input-error for="recipient" />
	@endif

	<!-- Url -->
	<div class="col-span-6 sm:col-span-4 mt-8">
		<x-form.label.label-optional for="url" value="{{ __('URL') }}" />
		<x-form.input.input-icon icon="fa-solid fa-link" id="url" type="text" class="mt-1 block w-full" wire:model="message.url" placeholder="https://example.org" />
		<x-input-error for="message.url" />
		<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
			{{ __('Please provide the URL you would like to share in the message.') }}
		</p>
	</div>

	<!-- Message -->
	<div class="col-span-6 sm:col-span-4 mt-8">
		<x-form.label.label-required for="message" value="{{ __('Message') }}" />
		<x-form.input.textarea
				id="message"
				autofocus
				placeholder="Leave a message ..."
				wire:model="message.message"
		></x-form.input.textarea>
		<x-input-error for="message.message" />
		<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
			Please provide the message you would like to send to the user. HTML is forbidden except for
			<x-typography.inline-code>&lt;b&gt;</x-typography.inline-code>
			and
			<x-typography.inline-code>&lt;i&gt;</x-typography.inline-code>
			tags.
		</p>
	</div>

</div>
