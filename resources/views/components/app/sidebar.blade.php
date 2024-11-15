<div>
    <!-- Sidebar backdrop (mobile only) -->
    <div
        class="fixed inset-0 bg-slate-900 bg-opacity-30 z-40 lg:hidden lg:z-auto transition-opacity duration-200"
        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        aria-hidden="true"
        x-cloak
    ></div>

    <!-- Sidebar -->
    <div
        id="sidebar"
        class="flex flex-col absolute z-40 left-0 top-0 lg:static lg:left-auto lg:top-auto lg:translate-x-0 h-screen overflow-y-scroll lg:overflow-y-auto no-scrollbar w-64 lg:w-20 lg:sidebar-expanded:!w-64 2xl:!w-64 shrink-0 bg-slate-800 p-4 transition-all duration-200 ease-in-out"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-64'"
        @click.outside="sidebarOpen = false"
        @keydown.escape.window="sidebarOpen = false"
        x-cloak="lg"
    >

        <!-- Sidebar header -->
        <div class="flex justify-start mb-10 pr-3 sm:px-2">
            <!-- Close button -->
            <button class="lg:hidden text-slate-500 hover:text-slate-400" @click.stop="sidebarOpen = !sidebarOpen" aria-controls="sidebar" :aria-expanded="sidebarOpen">
                <span class="sr-only">Close sidebar</span>
                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10.7 18.7l1.4-1.4L7.8 13H20v-2H7.8l4.3-4.3-1.4-1.4L4 12z" />
                </svg>
            </button>
            <!-- Logo -->
            <a class="flex items-center" href="{{ route('dashboard') }}">
				<img src="{{ asset('logo-192.png') }}" class="h-10 mr-3" alt="SearchTweak" />
				<div class="text-slate-400 text-xl lg:hidden lg:sidebar-expanded:block 2xl:block">
					Search<span class="font-bold">Tweak</span>
				</div>
			</a>
        </div>

        <!-- Links -->
        <div class="space-y-8">
            <!-- Pages group -->
            <div>
                <h3 class="text-xs uppercase text-slate-500 font-semibold pl-3">
                    <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
                    <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Menu</span>
                </h3>
                <ul class="mt-3">
					<!-- Dashboard -->
					<li class="px-3 py-2 rounded-sm mb-0.5 last:mb-0 @if(in_array(Request::segment(1), ['dashboard'])){{ 'bg-slate-900' }}@endif">
						<a class="block text-slate-200 hover:text-white truncate transition duration-150 @if(in_array(Request::segment(1), ['teams'])){{ 'hover:text-slate-200' }}@endif" href="{{ route('dashboard') }}">
							<div class="flex items-center justify-between">
								<div class="grow flex items-center">
									<svg class="shrink-0 h-6 w-6" viewBox="0 0 24 24">
										<path class="fill-current @if(in_array(Request::segment(1), ['dashboard'])){{ 'text-indigo-500' }}@else{{ 'text-slate-400' }}@endif" d="M12 0C5.383 0 0 5.383 0 12s5.383 12 12 12 12-5.383 12-12S18.617 0 12 0z" />
										<path class="fill-current @if(in_array(Request::segment(1), ['dashboard'])){{ 'text-indigo-600' }}@else{{ 'text-slate-600' }}@endif" d="M12 3c-4.963 0-9 4.037-9 9s4.037 9 9 9 9-4.037 9-9-4.037-9-9-9z" />
										<path class="fill-current @if(in_array(Request::segment(1), ['dashboard'])){{ 'text-indigo-200' }}@else{{ 'text-slate-400' }}@endif" d="M12 15c-1.654 0-3-1.346-3-3 0-.462.113-.894.3-1.285L6 6l4.714 3.301A2.973 2.973 0 0112 9c1.654 0 3 1.346 3 3s-1.346 3-3 3z" />
									</svg>
									<span class="text-sm font-medium ml-3 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Dashboard</span>
								</div>
							</div>
						</a>
					</li>

					<!-- Teams -->
					<li class="px-3 py-2 rounded-sm mb-0.5 last:mb-0 @if(in_array(Request::segment(1), ['teams'])){{ 'bg-slate-900' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['teams']) ? 1 : 0 }} }">
						<a class="block text-slate-200 hover:text-white truncate transition duration-150 @if(in_array(Request::segment(1), ['teams'])){{ 'hover:text-slate-200' }}@endif" href="#0" @click.prevent="sidebarExpanded ? open = !open : sidebarExpanded = true">
							<div class="flex items-center justify-between">
								<div class="flex items-center">
									<svg class="shrink-0 h-6 w-6" viewBox="0 0 24 24">
										<path class="fill-current @if(in_array(Request::segment(1), ['teams'])){{ 'text-indigo-500' }}@else{{ 'text-slate-600' }}@endif" d="M18.974 8H22a2 2 0 012 2v6h-2v5a1 1 0 01-1 1h-2a1 1 0 01-1-1v-5h-2v-6a2 2 0 012-2h.974zM20 7a2 2 0 11-.001-3.999A2 2 0 0120 7zM2.974 8H6a2 2 0 012 2v6H6v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5H0v-6a2 2 0 012-2h.974zM4 7a2 2 0 11-.001-3.999A2 2 0 014 7z" />
										<path class="fill-current @if(in_array(Request::segment(1), ['teams'])){{ 'text-indigo-300' }}@else{{ 'text-slate-400' }}@endif" d="M12 6a3 3 0 110-6 3 3 0 010 6zm2 18h-4a1 1 0 01-1-1v-6H6v-6a3 3 0 013-3h6a3 3 0 013 3v6h-3v6a1 1 0 01-1 1z" />
									</svg>
									<span class="text-sm font-medium ml-3 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Teams</span>
								</div>
								<!-- Icon -->
								<div class="flex shrink-0 ml-2 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">
									<svg class="w-3 h-3 shrink-0 ml-1 fill-current text-slate-400 @if(in_array(Request::segment(1), ['teams'])){{ 'rotate-180' }}@endif" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
										<path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
									</svg>
								</div>
							</div>
						</a>
						<div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
							<ul class="pl-9 mt-1 @if(!in_array(Request::segment(1), ['teams'])){{ 'hidden' }}@endif" :class="open ? '!block' : 'hidden'">
								<li class="mb-1 last:mb-0">
									<a class="block text-slate-400 hover:text-slate-200 transition duration-150 truncate @if(Route::is('teams.all')){{ '!text-indigo-500' }}@endif" href="{{ route('teams.all') }}">
										<span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">All Teams</span>
									</a>
								</li>
								<li class="mb-1 last:mb-0">
									<a class="block text-slate-400 hover:text-slate-200 transition duration-150 truncate @if(Route::is('teams.current')){{ '!text-indigo-500' }}@endif" href="{{ route('teams.current') }}">
										<span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Current Team</span>
									</a>
								</li>
							</ul>
						</div>
					</li>

					<!-- Endpoints -->
					@if (Gate::check('view-endpoints'))
					<li class="px-3 py-2 rounded-sm mb-0.5 last:mb-0 @if(in_array(Request::segment(1), ['endpoints'])){{ 'bg-slate-900' }}@endif">
						<a class="block text-slate-200 hover:text-white truncate transition duration-150 @if(in_array(Request::segment(1), ['endpoints'])){{ 'hover:text-slate-200' }}@endif" href="{{ route('endpoints') }}">
							<div class="flex items-center justify-between">
								<div class="grow flex items-center">
									<svg class="shrink-0 h-6 w-6" viewBox="0 0 24 24">
										<path class="fill-current @if(in_array(Request::segment(1), ['endpoints'])){{ 'text-indigo-500' }}@else{{ 'text-slate-600' }}@endif" d="M17.5 3A3.5 3.5 0 0 0 14 7L8.1 9.8A3.5 3.5 0 0 0 2 12a3.5 3.5 0 0 0 6.1 2.3l6 2.7-.1.5a3.5 3.5 0 1 0 1-2.3l-6-2.7a3.5 3.5 0 0 0 0-1L15 9a3.5 3.5 0 0 0 6-2.4c0-2-1.6-3.5-3.5-3.5Z"/>
									</svg>
									<span class="text-sm font-medium ml-3 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Endpoints</span>
								</div>
							</div>
						</a>
					</li>
					@endif

					<!-- Models -->
					@if (Gate::check('view-models'))
					<li class="px-3 py-2 rounded-sm mb-0.5 last:mb-0 @if(in_array(Request::segment(1), ['models'])){{ 'bg-slate-900' }}@endif">
						<a class="block text-slate-200 hover:text-white truncate transition duration-150 @if(in_array(Request::segment(1), ['models'])){{ 'hover:text-slate-200' }}@endif" href="{{ route('models') }}">
							<div class="flex items-center justify-between">
								<div class="grow flex items-center">
									<svg class="shrink-0 h-6 w-6" viewBox="0 0 24 24">
										<path class="fill-current @if(in_array(Request::segment(1), ['models'])){{ 'text-indigo-300' }}@else{{ 'text-slate-400' }}@endif" d="M13 15l11-7L11.504.136a1 1 0 00-1.019.007L0 7l13 8z" />
										<path class="fill-current @if(in_array(Request::segment(1), ['models'])){{ 'text-indigo-600' }}@else{{ 'text-slate-700' }}@endif" d="M13 15L0 7v9c0 .355.189.685.496.864L13 24v-9z" />
										<path class="fill-current @if(in_array(Request::segment(1), ['models'])){{ 'text-indigo-500' }}@else{{ 'text-slate-600' }}@endif" d="M13 15.047V24l10.573-7.181A.999.999 0 0024 16V8l-11 7.047z" />
									</svg>
									<span class="text-sm font-medium ml-3 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Models</span>
								</div>
							</div>
						</a>
					</li>
					@endif

					<!-- Evaluations -->
					@if (Gate::check('view-evaluations'))
					<li class="px-3 py-2 rounded-sm mb-0.5 last:mb-0 @if(in_array(Request::segment(1), ['evaluations'])){{ 'bg-slate-900' }}@endif">
						<a class="block text-slate-200 hover:text-white truncate transition duration-150 @if(in_array(Request::segment(1), ['evaluations'])){{ 'hover:text-slate-200' }}@endif" href="{{ route('evaluations') }}">
							<div class="flex items-center justify-between">
								<div class="grow flex items-center">
									<svg class="shrink-0 h-6 w-6" viewBox="0 0 24 24">
										<path class="fill-current @if(in_array(Request::segment(1), ['evaluations'])){{ 'text-indigo-300' }}@else{{ 'text-slate-400' }}@endif" d="M13 6.068a6.035 6.035 0 0 1 4.932 4.933H24c-.486-5.846-5.154-10.515-11-11v6.067Z" />
										<path class="fill-current @if(in_array(Request::segment(1), ['evaluations'])){{ 'text-indigo-500' }}@else{{ 'text-slate-700' }}@endif" d="M18.007 13c-.474 2.833-2.919 5-5.864 5a5.888 5.888 0 0 1-3.694-1.304L4 20.731C6.131 22.752 8.992 24 12.143 24c6.232 0 11.35-4.851 11.857-11h-5.993Z" />
										<path class="fill-current @if(in_array(Request::segment(1), ['evaluations'])){{ 'text-indigo-600' }}@else{{ 'text-slate-600' }}@endif" d="M6.939 15.007A5.861 5.861 0 0 1 6 11.829c0-2.937 2.167-5.376 5-5.85V0C4.85.507 0 5.614 0 11.83c0 2.695.922 5.174 2.456 7.17l4.483-3.993Z" />
									</svg>
									<span class="text-sm font-medium ml-3 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Evaluations</span>
								</div>
							</div>
						</a>
					</li>
					@endif

					<!-- Feedback -->
					@if (Gate::check('giveFeedbackGlobalPool', \App\Models\SearchEvaluation::class))
					<li class="px-3 py-2 rounded-sm mb-0.5 last:mb-0 @if(in_array(Request::segment(1), ['feedback'])){{ 'bg-slate-900' }}@endif">
						<a class="block text-slate-200 hover:text-white truncate transition duration-150 @if(in_array(Request::segment(1), ['feedback'])){{ 'hover:text-slate-200' }}@endif" href="{{ route('feedback') }}">
							<div class="flex items-center justify-between">
								<div class="grow flex items-center">
									<svg class="shrink-0 h-6 w-6" viewBox="0 0 24 24">
										<path class="fill-current @if(in_array(Request::segment(1), ['feedback'])){{ 'text-indigo-500' }}@else{{ 'text-slate-600' }}@endif" d="M8 1v2H3v19h18V3h-5V1h7v23H1V1z" />
										<path class="fill-current @if(in_array(Request::segment(1), ['feedback'])){{ 'text-indigo-500' }}@else{{ 'text-slate-600' }}@endif" d="M1 1h22v23H1z" />
										<path class="fill-current @if(in_array(Request::segment(1), ['feedback'])){{ 'text-indigo-300' }}@else{{ 'text-slate-400' }}@endif" d="M15 10.586L16.414 12 11 17.414 7.586 14 9 12.586l2 2zM5 0h14v4H5z" />
									</svg>
									<span class="text-sm font-medium ml-3 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Feedback</span>
								</div>

								<!-- Badge -->
								<livewire:sidebar.feedback-badge key="sidebar-feedback-badge" />
							</div>
						</a>
					</li>
					@endif

					<!-- Leaderboard -->
					@if (Gate::check('view-leaderboard'))
					<li class="px-3 py-2 rounded-sm mb-0.5 last:mb-0 @if(in_array(Request::segment(1), ['leaderboard'])){{ 'bg-slate-900' }}@endif">
						<a class="block text-slate-200 hover:text-white truncate transition duration-150 @if(in_array(Request::segment(1), ['leaderboard'])){{ 'hover:text-slate-200' }}@endif" href="{{ route('leaderboard') }}">
							<div class="flex items-center justify-between">
								<div class="grow flex items-center">
									<svg class="shrink-0 h-6 w-6" viewBox="0 0 24 24">
										<path class="fill-current @if(in_array(Request::segment(1), ['leaderboard'])){{ 'text-indigo-600' }}@else{{ 'text-slate-700' }}@endif" d="M4.418 19.612A9.092 9.092 0 0 1 2.59 17.03L.475 19.14c-.848.85-.536 2.395.743 3.673a4.413 4.413 0 0 0 1.677 1.082c.253.086.519.131.787.135.45.011.886-.16 1.208-.474L7 21.44a8.962 8.962 0 0 1-2.582-1.828Z" />
										<path class="fill-current @if(in_array(Request::segment(1), ['leaderboard'])){{ 'text-indigo-500' }}@else{{ 'text-slate-600' }}@endif" d="M10.034 13.997a11.011 11.011 0 0 1-2.551-3.862L4.595 13.02a2.513 2.513 0 0 0-.4 2.645 6.668 6.668 0 0 0 1.64 2.532 5.525 5.525 0 0 0 3.643 1.824 2.1 2.1 0 0 0 1.534-.587l2.883-2.882a11.156 11.156 0 0 1-3.861-2.556Z" />
										<path class="fill-current @if(in_array(Request::segment(1), ['leaderboard'])){{ 'text-indigo-300' }}@else{{ 'text-slate-400' }}@endif" d="M21.554 2.471A8.958 8.958 0 0 0 18.167.276a3.105 3.105 0 0 0-3.295.467L9.715 5.888c-1.41 1.408-.665 4.275 1.733 6.668a8.958 8.958 0 0 0 3.387 2.196c.459.157.94.24 1.425.246a2.559 2.559 0 0 0 1.87-.715l5.156-5.146c1.415-1.406.666-4.273-1.732-6.666Zm.318 5.257c-.148.147-.594.2-1.256-.018A7.037 7.037 0 0 1 18.016 6c-1.73-1.728-2.104-3.475-1.73-3.845a.671.671 0 0 1 .465-.129c.27.008.536.057.79.146a7.07 7.07 0 0 1 2.6 1.711c1.73 1.73 2.105 3.472 1.73 3.846Z" />
									</svg>
									<span class="text-sm font-medium ml-3 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Leaderboard</span>
								</div>
							</div>
						</a>
					</li>
					@endif

					<!-- Settings -->
					<li class="px-3 py-2 rounded-sm mb-0.5 last:mb-0 @if(in_array(Request::segment(1), ['user'])){{ 'bg-slate-900' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['user']) ? 1 : 0 }} }">
						<a class="block text-slate-200 hover:text-white truncate transition duration-150 @if(in_array(Request::segment(1), ['user'])){{ 'hover:text-slate-200' }}@endif" href="#0" @click.prevent="sidebarExpanded ? open = !open : sidebarExpanded = true">
							<div class="flex items-center justify-between">
								<div class="flex items-center">
									<svg class="shrink-0 h-6 w-6" viewBox="0 0 24 24">
										<path class="fill-current @if(in_array(Request::segment(1), ['user'])){{ 'text-indigo-500' }}@else{{ 'text-slate-600' }}@endif" d="M19.714 14.7l-7.007 7.007-1.414-1.414 7.007-7.007c-.195-.4-.298-.84-.3-1.286a3 3 0 113 3 2.969 2.969 0 01-1.286-.3z" />
										<path class="fill-current @if(in_array(Request::segment(1), ['user'])){{ 'text-indigo-300' }}@else{{ 'text-slate-400' }}@endif" d="M10.714 18.3c.4-.195.84-.298 1.286-.3a3 3 0 11-3 3c.002-.446.105-.885.3-1.286l-6.007-6.007 1.414-1.414 6.007 6.007z" />
										<path class="fill-current @if(in_array(Request::segment(1), ['user'])){{ 'text-indigo-500' }}@else{{ 'text-slate-600' }}@endif" d="M5.7 10.714c.195.4.298.84.3 1.286a3 3 0 11-3-3c.446.002.885.105 1.286.3l7.007-7.007 1.414 1.414L5.7 10.714z" />
										<path class="fill-current @if(in_array(Request::segment(1), ['user'])){{ 'text-indigo-300' }}@else{{ 'text-slate-400' }}@endif" d="M19.707 9.292a3.012 3.012 0 00-1.415 1.415L13.286 5.7c-.4.195-.84.298-1.286.3a3 3 0 113-3 2.969 2.969 0 01-.3 1.286l5.007 5.006z" />
									</svg>
									<span class="text-sm font-medium ml-3 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Settings</span>
								</div>
								<!-- Icon -->
								<div class="flex shrink-0 ml-2 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">
									<svg class="w-3 h-3 shrink-0 ml-1 fill-current text-slate-400 @if(in_array(Request::segment(1), ['user'])){{ 'rotate-180' }}@endif" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
										<path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
									</svg>
								</div>
							</div>
						</a>
						<div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
							<ul class="pl-9 mt-1 @if(!in_array(Request::segment(1), ['user'])){{ 'hidden' }}@endif" :class="open ? '!block' : 'hidden'">
								<li class="mb-1 last:mb-0">
									<a class="block text-slate-400 hover:text-slate-200 transition duration-150 truncate @if(Route::is('profile.show')){{ '!text-indigo-500' }}@endif" href="{{ route('profile.show') }}">
										<span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Profile</span>
									</a>
								</li>
							</ul>
						</div>
					</li>
                </ul>
            </div>

			@if (Gate::check('superuser'))
			<div>
				<h3 class="text-xs uppercase text-slate-500 font-semibold pl-3">
					<span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
					<span class="lg:hidden lg:sidebar-expanded:block 2xl:block">More</span>
				</h3>
				<ul class="mt-3">
					<!-- Administration -->
					<li class="px-3 py-2 rounded-sm mb-0.5 last:mb-0 @if(in_array(Request::segment(1), ['admin'])){{ 'bg-slate-900' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['admin']) ? 1 : 0 }} }">
						<a class="block text-slate-200 hover:text-white truncate transition duration-150 @if(in_array(Request::segment(1), ['admin'])){{ 'hover:text-slate-200' }}@endif" href="#0" @click.prevent="sidebarExpanded ? open = !open : sidebarExpanded = true">
							<div class="flex items-center justify-between">
								<div class="flex items-center">
									<svg class="shrink-0 h-6 w-6" viewBox="0 0 24 24">
										<path class="fill-current @if(in_array(Request::segment(1), ['admin'])){{ 'text-indigo-500' }}@else{{ 'text-slate-600' }}@endif" d="M8.07 16H10V8H8.07a8 8 0 110 8z" />
										<path class="fill-current @if(in_array(Request::segment(1), ['admin'])){{ 'text-indigo-300' }}@else{{ 'text-slate-400' }}@endif" d="M15 12L8 6v5H0v2h8v5z" />
									</svg>
									<span class="text-sm font-medium ml-3 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Administration</span>
								</div>
								<!-- Icon -->
								<div class="flex shrink-0 ml-2 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">
									<svg class="w-3 h-3 shrink-0 ml-1 fill-current text-slate-400 @if(in_array(Request::segment(1), ['admin'])){{ 'rotate-180' }}@endif" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
										<path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
									</svg>
								</div>
							</div>
						</a>
						<div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
							<ul class="pl-9 mt-1 @if(!in_array(Request::segment(1), ['admin'])){{ 'hidden' }}@endif" :class="open ? '!block' : 'hidden'">
								<li class="mb-1 last:mb-0">
									<a class="block text-slate-400 hover:text-slate-200 transition duration-150 truncate @if(Route::is('superuser.users')){{ '!text-indigo-500' }}@endif" href="{{ route('superuser.users') }}">
										<span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Users</span>
									</a>
								</li>
								<li class="mb-1 last:mb-0">
									<a class="block text-slate-400 hover:text-slate-200 transition duration-150 truncate" href="{{ route('horizon.index') }}">
										<span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Horizon</span>
									</a>
								</li>
							</ul>
						</div>
					</li>
				</ul>
			</div>
			@endif
        </div>

        <!-- Expand / collapse button -->
        <div class="pt-3 hidden lg:inline-flex 2xl:hidden justify-end mt-auto">
            <div class="px-3 py-2">
                <button @click="sidebarExpanded = !sidebarExpanded">
                    <span class="sr-only">Expand / collapse sidebar</span>
                    <svg class="w-6 h-6 fill-current sidebar-expanded:rotate-180" viewBox="0 0 24 24">
                        <path class="text-slate-400" d="M19.586 11l-5-5L16 4.586 23.414 12 16 19.414 14.586 18l5-5H7v-2z" />
                        <path class="text-slate-600" d="M3 23H1V1h2z" />
                    </svg>
                </button>
            </div>
        </div>

    </div>
</div>
