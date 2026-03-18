<form wire:submit="saveUser" id="user-form" x-data="{
	passwordTab: 'manual',
	passwordCopied: false,
	activeClasses: 'inline-block p-4 border-b-2 rounded-t-lg text-blue-600 hover:text-blue-600 dark:text-blue-500 dark:hover:text-blue-500 border-blue-600 dark:border-blue-500',
	inactiveClasses: 'inline-block p-4 border-b-2 rounded-t-lg text-gray-600 hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-400 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-500',
	generatePassword() {
		const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
		let password = '';
		const array = new Uint32Array(16);
		crypto.getRandomValues(array);
		for (let i = 0; i < 16; i++) {
			password += chars[array[i] % chars.length];
		}
		this.$refs.generatedPasswordInput.value = password;
		$wire.set('userForm.password', password);
	},
	copyPassword() {
		const input = this.$refs.generatedPasswordInput;
		if (!input) return;

		const value = input.value || '';
		if (!value) return;

		const onSuccess = () => {
			this.passwordCopied = true;
			setTimeout(() => this.passwordCopied = false, 2000);
		};

		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(value).then(onSuccess);
			return;
		}

		input.select();
		document.execCommand('copy');
		onSuccess();
	}
}">
	<div class="px-4 py-5 bg-white dark:bg-slate-800 sm:p-6">

		<!-- Name -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-required for="userForm.name" value="Name" />
			<x-input type="text" wire:model="userForm.name" id="userForm.name" autocomplete="off" placeholder="John Doe" />
			<x-input-error for="userForm.name" />
		</div>

		<!-- Email -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-required for="userForm.email" value="Email" />
			<x-form.input.input-icon icon="fa-solid fa-envelope" id="userForm.email" type="email" wire:model="userForm.email" placeholder="name@example.org" autocomplete="off" />
			<x-input-error for="userForm.email" />
		</div>

		<!-- Password Tabs -->
		<div class="mb-8 last:mb-0">
			<x-form.label.label-required value="Password" />
			<div class="mb-4 border-b border-gray-200 dark:border-gray-700">
				<ul class="flex flex-wrap -mb-px text-sm font-medium text-center" role="tablist">
					<li role="presentation">
						<button :class="passwordTab == 'manual' ? activeClasses : inactiveClasses" type="button" @click="passwordTab = 'manual'">Enter Password</button>
					</li>
					<li role="presentation">
						<button :class="passwordTab == 'generate' ? activeClasses : inactiveClasses" type="button" @click="passwordTab = 'generate'">Generate Password</button>
					</li>
				</ul>
			</div>

			<!-- Manual Password Tab -->
			<div x-show="passwordTab == 'manual'">
				<x-input type="password" wire:model="userForm.password" id="userForm.password" autocomplete="new-password" placeholder="Enter password" />
			</div>

			<!-- Generate Password Tab -->
			<div x-show="passwordTab == 'generate'">
				<div class="flex items-center gap-2">
					<div class="relative w-full">
						<input
							type="text"
							x-ref="generatedPasswordInput"
							class="mt-1 block p-2.5 pe-10 w-full text-sm text-gray-900 rounded-lg border border-gray-300 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
							placeholder="Click Generate to create a password"
							readonly
						>
						<button
							type="button"
							@click.prevent="copyPassword()"
							class="absolute inset-y-0 end-0 flex items-center px-3 mt-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
							title="{{ __('Copy') }}"
						>
							<i x-show="!passwordCopied" class="fa-regular fa-copy"></i>
							<i x-show="passwordCopied" x-transition class="fa-solid fa-check text-green-600 dark:text-green-400"></i>
						</button>
					</div>
					<button
						type="button"
						@click="generatePassword()"
						class="mt-1 inline-flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 dark:bg-gray-600 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-500 whitespace-nowrap"
					>
						Generate
					</button>
				</div>
			</div>

			<x-input-error for="userForm.password" />
			<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
				{{ $this->userForm->passwordHint() }}
			</p>
		</div>

	</div>
</form>
