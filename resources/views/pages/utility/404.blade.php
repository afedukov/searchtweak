<x-app-layout background="bg-white dark:bg-slate-900">
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <div class="max-w-2xl m-auto mt-16">

            <div class="text-center px-4">
                <div class="inline-flex mb-8">
                    <img class="max-w-[300px]" src="{{ asset('404.png') }}" alt="404 Not Found" />
                </div>
				<h1 class="text-4xl font-extrabold text-gray-900 dark:text-gray-100 mb-4">Page Not Found</h1>
                <div class="mb-6">Sorry, we couldn't find that page. Try going back to the previous page or visit the dashboard.</div>
                <a href="{{ route('dashboard') }}" class="btn bg-indigo-500 hover:bg-indigo-600 text-white">Back To Dashboard</a>
            </div>

        </div>

    </div>
</x-app-layout>
