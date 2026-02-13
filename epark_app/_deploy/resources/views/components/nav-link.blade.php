@props(['href', 'active'])

@php
    // Définition des classes de base communes
    $baseClasses = 'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg transition-all duration-200 relative';
    
    // Classes pour l'état ACTIF
    $activeClasses = 'bg-indigo-600 text-white shadow-md ring-2 ring-indigo-500 ring-offset-2';
    
    // Classes pour l'état INACTIF
    $inactiveClasses = 'text-gray-800 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2';
    
    // Fusion des classes selon l'état
    $computedClasses = $active 
        ? $baseClasses . ' ' . $activeClasses 
        : $baseClasses . ' ' . $inactiveClasses;
@endphp

<a href="{{ $href }}"
   {{ $attributes->merge(['class' => $computedClasses]) }}
>
    {{ $slot }}
    
    {{-- Indicateur visuel subtil quand actif (Optionnel) --}}
    @if($active)
        <span class="absolute top-2 right-2 flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-200 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-100"></span>
        </span>
    @endif
</a>