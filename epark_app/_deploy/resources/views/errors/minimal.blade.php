<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erreur {{ $exception->getStatusCode() }} - ePark</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-gray-50 via-white to-blue-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full text-center">
        <!-- Code d'erreur -->
        <div class="mb-8">
            <div class="inline-flex items-center justify-center w-32 h-32 bg-gradient-to-br from-gray-400 to-gray-600 rounded-full shadow-2xl mb-4">
                <span class="text-5xl font-black text-white">{{ $exception->getStatusCode() }}</span>
            </div>
        </div>

        <!-- Message principal -->
        <h1 class="text-5xl font-black text-gray-900 mb-4">
            @switch($exception->getStatusCode())
                @case(403)
                    Accès refusé
                    @break
                @case(419)
                    Session expirée
                    @break
                @case(429)
                    Trop de requêtes
                    @break
                @case(503)
                    Service indisponible
                    @break
                @default
                    Une erreur est survenue
            @endswitch
        </h1>
        
        <p class="text-lg text-gray-600 mb-8">
            @switch($exception->getStatusCode())
                @case(403)
                    Vous n'avez pas les permissions nécessaires pour accéder à cette ressource.
                    @break
                @case(419)
                    Votre session a expiré. Veuillez rafraîchir la page et réessayer.
                    @break
                @case(429)
                    Vous avez effectué trop de requêtes. Veuillez patienter quelques instants.
                    @break
                @case(503)
                    Le service est temporairement indisponible. Veuillez réessayer dans quelques instants.
                    @break
                @default
                    {{ $exception->getMessage() ?: 'Une erreur inattendue s\'est produite.' }}
            @endswitch
        </p>

        @if(app()->environment('local') && isset($exception))
            <div class="mb-8 p-6 bg-yellow-50 border-2 border-yellow-200 rounded-2xl text-left">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h3 class="font-black text-yellow-900 mb-2">Détails (mode développement)</h3>
                        <p class="text-sm font-bold text-yellow-800 mb-1">{{ get_class($exception) }}</p>
                        <p class="text-sm text-yellow-700 font-medium">{{ $exception->getMessage() }}</p>
                        @if($exception->getFile())
                            <div class="text-xs text-yellow-600 font-mono bg-white rounded-lg p-3 mt-3">
                                <strong>Fichier :</strong> {{ $exception->getFile() }}<br>
                                <strong>Ligne :</strong> {{ $exception->getLine() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="javascript:history.back()" class="inline-flex items-center gap-2 px-8 py-4 bg-white text-gray-700 border-2 border-gray-200 rounded-xl font-bold hover:bg-gray-50 transition-all shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Retour
            </a>
            <a href="/" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg shadow-indigo-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Accueil
            </a>
        </div>
    </div>
</body>
</html>
