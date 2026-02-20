@props(['label' => 'AI'])

<span @class([
	'inline-flex items-center text-[10px] leading-none font-bold uppercase tracking-wide me-2 px-1.5 py-0.5 rounded',
	'bg-indigo-500 text-white',
])>
	{{ $label }}
</span>
