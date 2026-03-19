<div>
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-700 leading-tight dark:text-slate-300">
			{{ __('Settings') }}
		</h2>
	</x-slot>

	<div>
		<div class="px-4 sm:px-5 lg:px-8 py-8 w-full max-w-9xl mx-auto">
			<div class="col-span-full xl:col-span-8 bg-white dark:bg-slate-800 shadow-lg rounded-sm border border-slate-200 dark:border-slate-700">
				<header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
					<h2 class="font-bold text-slate-800 dark:text-slate-100">
						{{ __('Authentication') }}
					</h2>
				</header>

				<div class="p-5 space-y-6">

					<!-- Registration -->
					<div class="flex items-center justify-between">
						<div>
							<h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Registration') }}</h3>
							<p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Allow new users to register accounts.') }}</p>
						</div>
						<label class="inline-flex items-center cursor-pointer">
							<input type="checkbox" wire:model.live="registration" class="sr-only peer">
							<div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
						</label>
					</div>

					<!-- Password Reset -->
					<div class="flex items-center justify-between">
						<div>
							<h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Password Reset') }}</h3>
							<p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Allow users to reset their passwords via email.') }}</p>
						</div>
						<label class="inline-flex items-center cursor-pointer">
							<input type="checkbox" wire:model.live="resetPasswords" class="sr-only peer">
							<div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
						</label>
					</div>

					<!-- Email Verification -->
					<div class="flex items-center justify-between">
						<div>
							<h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Email Verification') }}</h3>
							<p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Require users to verify their email address.') }}</p>
						</div>
						<label class="inline-flex items-center cursor-pointer">
							<input type="checkbox" wire:model.live="emailVerification" class="sr-only peer">
							<div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
						</label>
					</div>

					<!-- Two-Factor Authentication -->
					<div class="flex items-center justify-between">
						<div>
							<h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Two-Factor Authentication') }}</h3>
							<p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Allow users to enable two-factor authentication.') }}</p>
						</div>
						<label class="inline-flex items-center cursor-pointer">
							<input type="checkbox" wire:model.live="twoFactorAuthentication" class="sr-only peer">
							<div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
						</label>
					</div>

				</div>
			</div>
		</div>
	</div>
</div>
