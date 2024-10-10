<header class="fixed w-full">
	<nav class="bg-white border-gray-200 py-2.5 dark:bg-gray-900">
		<div class="flex flex-wrap items-center justify-between max-w-screen-xl px-4 mx-auto gap-y-2">
			<a href="{{ route('dashboard') }}" class="flex items-center">
				<img src="{{ asset('logo-192.png') }}" class="h-6 mr-3 sm:h-10" alt="SearchTweak" />
				<span class="self-center text-xl font-medium whitespace-nowrap dark:text-white hidden sm:inline">
					Search<span class="font-bold">Tweak</span>
				</span>
			</a>
			<div class="flex items-center lg:order-2">

				<x-theme-toggle />

				<!-- Log In -->
				<a href="{{ route('login') }}" class="ml-2 text-gray-800 dark:text-white hover:bg-gray-100 font-medium rounded-lg text-sm px-4 lg:px-5 py-2 lg:py-2.5 sm:mr-2 dark:hover:bg-gray-700 focus:outline-none">
					Log In
				</a>

				<!-- Get Started -->
				<a href="{{ route('register') }}" class="ml-2 text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-4 lg:px-5 py-2 lg:py-2.5 sm:mr-2 lg:mr-0 focus:outline-none">
					Get Started
				</a>

				<button data-collapse-toggle="mobile-menu-2" type="button" class="inline-flex items-center p-2 ml-1 text-sm text-gray-500 rounded-lg lg:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600" aria-controls="mobile-menu-2" aria-expanded="false">
					<span class="sr-only">Open main menu</span>
					<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
					<svg class="hidden w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
				</button>
			</div>

		</div>
	</nav>
</header>
