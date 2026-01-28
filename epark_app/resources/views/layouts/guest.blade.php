<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ePark') }} - Connexion</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    
    <body class="font-sans text-gray-900 antialiased bg-gray-50">
        
        <!-- Conteneur principal : Prend toute la hauteur et centre le contenu -->
        <div class="min-h-screen flex flex-col items-center justify-center px-4 sm:px-6 lg:px-8 relative overflow-hidden">
            
            <!-- Décoration d'arrière-plan (Cercles flous pour l'effet moderne) -->
            <div class="absolute top-0 left-0 w-64 h-64 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
            <div class="absolute top-0 right-0 w-64 h-64 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
            <div class="absolute -bottom-8 left-20 w-64 h-64 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>

            <!-- Section du Logo ePark -->
            <div class="text-center mb-8 z-10">
                <a href="/" class="inline-flex items-center gap-3 group">
                    <!-- Icône SVG stylisée -->
                    <div class="bg-gradient-to-br from-indigo-600 to-purple-600 w-12 h-12 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-200 group-hover:scale-105 transition-transform duration-300">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <!-- Texte du Logo -->
                    <span class="text-4xl font-extrabold text-gray-800 tracking-tight">ePark</span>
                </a>
                <p class="mt-2 text-sm text-gray-500">La solution de parking intelligente.</p>
            </div>

            <!-- Carte du Formulaire -->
            <div class="w-full max-w-md bg-white shadow-xl rounded-2xl border border-gray-100 overflow-hidden z-10">
                <!-- Bandeau coloré en haut de la carte (Optionnel, pour le style) -->
                <div class="h-2 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>
                
                <div class="px-8 py-8">
                    {{ $slot }}
                </div>
            </div>

            <!-- Footer Copyright -->
            <div class="mt-8 text-center text-xs text-gray-400 z-10">
                &copy; {{ date('Y') }} ePark Inc. Tous droits réservés.
            </div>
        </div>

        <!-- Styles supplémentaires pour l'animation de fond (si besoin) -->
        <style>
            @keyframes blob {
                0% { transform: translate(0px, 0px) scale(1); }
                33% { transform: translate(30px, -50px) scale(1.1); }
                66% { transform: translate(-20px, 20px) scale(0.9); }
                100% { transform: translate(0px, 0px) scale(1); }
            }
            .animate-blob {
                animation: blob 7s infinite;
            }
            .animation-delay-2000 {
                animation-delay: 2s;
            }
            .animation-delay-4000 {
                animation-delay: 4s;
            }
        </style>
    </body>
</html>