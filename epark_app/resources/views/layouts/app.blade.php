<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ePark') }} - {{ isset($title) ? $title : 'Tableau de bord' }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('logo/ePark.png') }}" />

        <!-- Police Figtree (Moderne & Rond) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Nouveau CSS moderne (chargé après Vite pour écraser si nécessaire) -->
    <link rel="stylesheet" href="{{ asset('build/assets/app-custom.css') }}" />
    </head>
    
    <!-- Fond de page global -->
    <body class="font-sans antialiased text-gray-900 bg-gray-50 flex flex-col min-h-screen">
        
        <!-- Navigation Flottante -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Logo Gauche -->
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <!-- Logo ePark stylisé (SVG inline pour éviter les soucis d'images) -->
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 group">
                                <div class="bg-indigo-600 text-white p-1.5 rounded-lg group-hover:bg-indigo-700 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <span class="font-bold text-xl tracking-tight text-gray-800">ePark</span>
                            </a>
                        </div>
                    </div>

                    <!-- Navigation Links (Droite) -->
                    <div class="hidden sm:ms-6 sm:flex sm:items-center">
                        <!-- Ici on inclut ta navigation existante -->
                        @include('layouts.navigation')
                    </div>

                    <!-- Menu Mobile (Bouton hamburger) -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500" aria-controls="mobile-menu" aria-expanded="false">
                            <span class="sr-only">Ouvrir le menu principal</span>
                            <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- En-tête de page optionnel (Header) -->
        @isset($header)
            <header class="bg-white border-b border-gray-200">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <h1 class="text-2xl font-bold text-gray-900 leading-tight">
                        {{ $header }}
                    </h1>
                </div>
            </header>
        @endisset

        <!-- Contenu Principal -->
        <main class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Card Container pour un look propre -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 min-h-[500px]">
                <div class="p-6 sm:p-8">
                        {{ $slot ?? '' }}
                </div>
            </div>
        </main>

        <!-- Footer Simple -->
        <footer class="bg-white border-t border-gray-200 mt-auto">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex justify-between items-center text-sm text-gray-500">
                <p>&copy; {{ date('Y') }} ePark. Tous droits réservés.</p>
                <div class="flex space-x-4">
                    <a href="#" class="hover:text-indigo-600 transition-colors">Support</a>
                    <a href="#" class="hover:text-indigo-600 transition-colors">Mentions Légales</a>
                </div>
            </div>
        </footer>

        <!-- Scripts JS (Flash messages, etc.) -->
        <script>
            // Script simple pour fermer les alertes automatiquement
            document.addEventListener('DOMContentLoaded', () => {
                const alerts = document.querySelectorAll('[data-dismiss="alert"]');
                alerts.forEach(alert => {
                    setTimeout(() => {
                        alert.style.opacity = '0';
                        setTimeout(() => alert.remove(), 300);
                    }, 5000);
                });
            });
        </script>
    </body>
</html>