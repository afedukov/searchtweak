<x-guest-layout>
	<section class="bg-white dark:bg-gray-900">
		<div class="grid max-w-screen-xl px-4 pt-20 pb-8 mx-auto lg:gap-8 xl:gap-0 lg:py-16 lg:grid-cols-12 lg:pt-28">

			<div class="mr-auto place-self-center lg:col-span-7">
				@isset($title)
					<h1 class="max-w-2xl mb-4 text-xl text-gray-700 font-extrabold leading-none tracking-tight md:text-2xl xl:text-3xl dark:text-white">
						{{ $title }}
					</h1>

					<x-hr />
				@endisset

				{{ $slot }}
			</div>

		</div>
	</section>
</x-guest-layout>
