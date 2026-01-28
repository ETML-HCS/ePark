@props(['href', 'active'])

<a 
    {{ $attributes->merge(['class' => $active 
        ? 'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 shadow-sm transition-all duration-200' 
        : 'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-all duration-200']) }} 
    href="{{ $href }}"
>
    {{ $slot }}
</a>