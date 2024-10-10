@props(['form'])

<x-form.input.textarea rows="5" class="font-mono text-gray-400" placeholder="Provide a mapper code ..." wire:model="{{ $form }}.mapper_code"></x-form.input.textarea>
<x-input-error for="{{ $form }}.mapper_code" />
<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
	Please specify how to extract specific data attributes from the response. The data is structured in a
	multi-dimensional array format and is flattened into a single-level array using "dot" notation, indicating depth.
</p>
<p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
	For more information, refer to the <a href="/docs/1.0/mapper-code" target="_blank" class="text-blue-500 hover:underline">Mapper Code</a> documentation.
</p>
