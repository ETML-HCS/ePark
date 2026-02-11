@props([
    'variant' => 'primary', // Permet de changer la couleur si besoin (primary, danger, etc.)
])

@php
    // Classes de base communes
    $baseClasses = 'inline-flex items-center justify-center px-4 py-2 border font-medium text-sm rounded-lg shadow-sm transition-all duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed active:scale-95';

    // Variantes de couleurs (Extensible pour les boutons rouge, vert, etc.)
    $variants = [
        'primary' => 'bg-indigo-600 border-transparent text-white hover:bg-indigo-700 active:bg-indigo-800 focus:ring-indigo-500 focus:ring-offset-white dark:focus:ring-offset-gray-800 disabled:hover:bg-indigo-600',
        // Exemple pour un bouton 'danger' (si tu veux l'utiliser plus tard) :
        // 'danger' => 'bg-red-600 border-transparent text-white hover:bg-red-700 focus:ring-red-500',
    ];

    // Fusion de tout
    $computedClasses = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

<button {{ $attributes->merge(['type' => 'submit', 'class' => $computedClasses]) }}>
    {{ $slot }}
</button>