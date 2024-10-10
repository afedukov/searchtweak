@props(['for'])

@error($for)
    <p {{ $attributes->merge(['class' => 'mt-2 text-sm text-rose-500 dark:text-rose-400']) }}>{{ $message }}</p>
@enderror
