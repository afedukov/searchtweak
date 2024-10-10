<x-tooltip-info {{ $attributes->merge(['class' => 'ml-2 mb-1']) }}>
	<div class="w-96">
		<h3 class="font-semibold mb-2">Mapper Code</h3>
		<p class="mb-2">
			Using a dotted notation format, you define paths to access desired information within the response.
			The data is structured in a multi-dimensional array format and is flattened into a single-level array
			using "dot" notation, indicating depth.
		</p>
		<p class="mb-2">
			In this context, <x-typography.inline-code class="text-xs">data</x-typography.inline-code> refers to the document root,
			serving as the starting point for data extraction.
		</p>
		<p class="mb-2">
			For example, you can specify that the <x-typography.inline-code class="text-xs">id</x-typography.inline-code> attribute
			is located at <x-typography.inline-code class="text-xs">data.items.*.id</x-typography.inline-code>. This means that for
			each item within the <x-typography.inline-code class="text-xs">items</x-typography.inline-code> property array in the
			search response, the system will extract its corresponding ID. Similarly, other attributes such
			as <x-typography.inline-code class="text-xs">name</x-typography.inline-code>, <x-typography.inline-code class="text-xs">image</x-typography.inline-code>
			can be defined in a similar manner. Additionally, to access an array item with a specific index
			<x-typography.inline-code class="text-xs">x</x-typography.inline-code>, you can use the notation
			<x-typography.inline-code class="text-xs">items.x.value</x-typography.inline-code>, for example
			<x-typography.inline-code class="text-xs">images.0.url</x-typography.inline-code>.
		</p>
		<p class="mb-2">
			To define a document image, you can specify the <x-typography.inline-code class="text-xs">image</x-typography.inline-code> attribute,
			which should contain the URL of the image.
		</p>
		<p>
			While <x-typography.inline-code>id</x-typography.inline-code> and <x-typography.inline-code>name</x-typography.inline-code>
			are mandatory for the mapper code, other attributes are optional.
		</p>
	</div>
</x-tooltip-info>
