@props(['as' => 'a'])

@php
	$baseClasses = 'block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out';
@endphp

@if ($as === 'button')
	<button type="submit" {{ $attributes->merge(['class' => $baseClasses])->except('as') }}>
		{{ $slot }}
	</button>
@else
	<a {{ $attributes->merge(['class' => $baseClasses])->except('as') }}>{{ $slot }}</a>
@endif
