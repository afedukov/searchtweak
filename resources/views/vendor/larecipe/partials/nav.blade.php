<div class="fixed pin-t pin-x z-40">
    <div class="bg-gradient-primary text-white h-1"></div>

    <nav class="flex items-center justify-between text-black bg-navbar shadow-xs h-16">
        <div class="flex items-center flex-no-shrink">
			<a href="{{ route('dashboard') }}" class="flex items-center mx-4">
				<img src="{{ asset('logo-192.png') }}" class="h-6 mr-3 sm:h-10" alt="SearchTweak" />
				<span class="self-center text-xl font-medium whitespace-nowrap dark:text-white">
					Search<span class="font-bold">Tweak</span>
				</span>
			</a>

            <div class="switch">
                <input type="checkbox" name="1" id="1" v-model="sidebar" class="switch-checkbox" />
                <label class="switch-label" for="1"></label>
            </div>
        </div>

        <div class="mx-4 flex items-center">
            @if(config('larecipe.search.enabled'))
                <larecipe-button id="search-button"
                    :type="searchBox ? 'primary' : 'link'"
                    @click="searchBox = ! searchBox"
                    class="px-4">
                    <i class="fas fa-search" id="search-button-icon"></i>
                </larecipe-button>
            @endif
        </div>
    </nav>
</div>
