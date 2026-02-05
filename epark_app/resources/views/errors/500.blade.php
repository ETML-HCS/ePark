<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erreur 500 - Erreur serveur</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-red-50 via-white to-orange-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full text-center">
        <!-- Icône d'erreur animée -->
        <div class="mb-8 animate-bounce">
            <div class="inline-flex items-center justify-center w-32 h-32 bg-gradient-to-br from-red-500 to-orange-600 rounded-full shadow-2xl">
                <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
        </div>

        <!-- Message principal -->
        <h1 class="text-6xl font-black text-gray-900 mb-4">Oups !</h1>
        <h2 class="text-2xl font-bold text-gray-700 mb-4">Erreur serveur interne</h2>
        <p class="text-lg text-gray-600 mb-8">
            Une erreur inattendue s'est produite. Notre équipe a été informée et travaille à résoudre le problème.
        </p>

        @if(app()->environment('local') && isset($exception))
            <div class="mb-8 p-6 bg-red-50 border-2 border-red-200 rounded-2xl text-left">
                <div class="flex items-start gap-3 mb-4">
                    <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h3 class="font-black text-red-900 mb-2">Détails de l'erreur (mode développement)</h3>
                        <p class="text-sm font-bold text-red-800 mb-2">{{ get_class($exception) }}</p>
                        <p class="text-sm text-red-700 mb-4 font-medium">{{ $exception->getMessage() }}</p>
                        
                        @if($exception->getFile())
                            <div class="text-xs text-red-600 font-mono bg-white rounded-lg p-3 mb-3">
                                <strong>Fichier :</strong> {{ $exception->getFile() }}<br>
                                <strong>Ligne :</strong> {{ $exception->getLine() }}
                            </div>
                        @endif

                        @if(method_exists($exception, 'getTrace'))
                            <details class="text-xs text-red-600">
                                <summary class="cursor-pointer font-bold hover:text-red-800 mb-2">Voir la stack trace</summary>
                                <div class="mt-2 p-3 bg-white rounded-lg font-mono overflow-auto max-h-96">
                                    @foreach($exception->getTrace() as $index => $trace)
                                        <div class="mb-2 pb-2 border-b border-red-100">
                                            <strong class="text-red-800">{{ $index }}.</strong> 
                                            @if(isset($trace['file']))
                                                <span class="text-gray-900">{{ $trace['file'] }}</span>
                                                <span class="text-gray-600">:{{ $trace['line'] ?? '?' }}</span>
                                            @endif
                                            @if(isset($trace['function']))
                                                <div class="mt-1 text-gray-700">
                                                    {{ $trace['class'] ?? '' }}{{ $trace['type'] ?? '' }}{{ $trace['function'] }}()
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </details>
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

        <!-- Info supplémentaire -->
        <p class="mt-8 text-sm text-gray-500">
            Si le problème persiste, veuillez contacter notre support technique.
        </p>
    </div>
</body>
</html>
