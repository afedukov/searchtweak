<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn bg-indigo-500 hover:bg-indigo-600 text-white whitespace-nowrap disabled:opacity-25']) }}>
    {{ $slot }}
</button>
