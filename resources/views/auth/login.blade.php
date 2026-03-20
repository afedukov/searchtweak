<x-authentication-layout>
    <h1 class="text-3xl text-gray-700 dark:text-white font-bold mb-6">{{ __('Welcome Back') }}</h1>
    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    @inject('settingsService', 'App\Services\SettingsService')

    @php
        $ssoEnabled = $settingsService->isSsoEnabled();
        $ssoOnlyMode = $settingsService->isSsoOnlyMode();
        $fallback = request()->has('fallback');
        $showForm = !$ssoOnlyMode || $fallback;
    @endphp

    @if ($ssoEnabled)
        <!-- SSO Button -->
        <a href="{{ route('oidc.redirect') }}"
           class="w-full inline-flex items-center justify-center px-4 py-2.5 mb-2 bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-lg text-sm focus:outline-none transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            {{ config('services.keycloak.button_label') }}
        </a>

        @if ($ssoOnlyMode && !$fallback)
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400 text-center">
                {{ __('Use your corporate account to sign in.') }}
            </p>
        @endif

        @if ($showForm)
            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-200 dark:border-slate-700"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white dark:bg-slate-900 text-gray-500 dark:text-gray-400">{{ __('or') }}</span>
                </div>
            </div>
        @endif
    @endif

    @if ($showForm)
    <!-- Form -->
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="space-y-4">
            <div>
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" type="email" name="email" :value="old('email')" required autofocus placeholder="name@company.com" />
            </div>
            <div>
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" type="password" name="password" required autocomplete="current-password" />
            </div>
			<div class="mr-1">
				<label class="flex items-center" name="newsletter" id="newsletter">
					<input type="checkbox" class="form-checkbox" id="remember_me" name="remember" />
					<span class="text-sm ml-2">{{ __('Remember me') }}</span>
				</label>
			</div>
        </div>
        <div class="flex items-center justify-between mt-6">
            @if (Route::has('password.request'))
                <div class="mr-1">
                    <a class="text-sm underline hover:no-underline" href="{{ route('password.request') }}">
                        {{ __('Forgot Password?') }}
                    </a>
                </div>
            @endif
            <x-button class="ml-auto">
                {{ __('Sign in') }}
            </x-button>
        </div>
    </form>
    <x-validation-errors class="mt-4" />
    <!-- Footer -->
    @if (Route::has('register'))
    <div class="pt-5 mt-6 border-t border-slate-200 dark:border-slate-700">
        <div class="text-sm">
            {{ __('Don\'t you have an account?') }} <a class="font-medium text-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400" href="{{ route('register') }}">{{ __('Sign Up') }}</a>
        </div>
    </div>
    @endif
    @endif
</x-authentication-layout>
