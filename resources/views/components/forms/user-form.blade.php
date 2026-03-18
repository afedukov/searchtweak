<form wire:submit="saveUser" id="user-form" x-data="{
	generatedPassword: '',
	passwordCopied: false,
	generatePassword() {
		const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
		let password = '';
		const array = new Uint32Array(16);
		crypto.getRandomValues(array);
		for (let i = 0; i < 16; i++) {
			password += chars[array[i] % chars.length];
		}
		this.generatedPassword = password;
		$wire.set('userForm.password', password);
		$wire.set('userForm.password_confirmation', password);
	},
	copyPassword() {
		const value = this.generatedPassword;
		const onSuccess = () => {
			this.passwordCopied = true;
			setTimeout(() => this.passwordCopied = false, 1500);
		};
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(value).then(onSuccess);
			return;
		}
		const ta = document.createElement('textarea');
		ta.value = value;
		ta.style.position = 'fixed';
		ta.style.opacity = '0';
		document.body.appendChild(ta);
		ta.select();
		document.execCommand('copy');
		document.body.removeChild(ta);
		onSuccess();
	}
}">
	<div class="px-4 py-5 bg-white dark:bg-slate-800 sm:p-6">

		<!-- Name -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-required for="userForm.name" value="Name" />
			<x-input type="text" wire:model="userForm.name" id="userForm.name" autocomplete="off" />
			<x-input-error for="userForm.name" />
		</div>

		<!-- Email -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-required for="userForm.email" value="Email" />
			<x-input type="email" wire:model="userForm.email" id="userForm.email" autocomplete="off" />
			<x-input-error for="userForm.email" />
		</div>

		<!-- Password -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-required for="userForm.password" value="Password" />
			<div class="flex items-center gap-2">
				<div class="w-full">
					<x-input x-bind:type="generatedPassword ? 'text' : 'password'" wire:model="userForm.password" id="userForm.password" autocomplete="new-password" />
				</div>
				<button
					type="button"
					@click="generatePassword()"
					class="mt-1 inline-flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 dark:bg-gray-600 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-500 whitespace-nowrap"
				>
					Generate
				</button>
				<button
					x-show="generatedPassword"
					x-cloak
					type="button"
					@click="copyPassword()"
					class="mt-1 inline-flex items-center px-2 py-2.5 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
					title="Copy to clipboard"
				>
					<svg x-show="!passwordCopied" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M8 4v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7.242a2 2 0 0 0-.602-1.43L16.083 2.57A2 2 0 0 0 14.685 2H10a2 2 0 0 0-2 2Z" />
						<path stroke-linecap="round" stroke-linejoin="round" d="M16 18v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h2" />
					</svg>
					<svg x-show="passwordCopied" x-cloak class="w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
					</svg>
				</button>
			</div>
			<x-input-error for="userForm.password" />
		</div>

		<!-- Confirm Password -->
		<div class="mb-8 last:mb-0" x-show="!generatedPassword">
			<x-form.label.label-required for="userForm.password_confirmation" value="Confirm Password" />
			<x-input type="password" wire:model="userForm.password_confirmation" id="userForm.password_confirmation" autocomplete="new-password" />
			<x-input-error for="userForm.password_confirmation" />
		</div>

	</div>
</form>
