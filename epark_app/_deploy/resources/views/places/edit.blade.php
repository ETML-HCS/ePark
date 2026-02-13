<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('places.mes') }}" class="p-2 bg-white rounded-xl hover:bg-gray-50 transition-all shadow-sm">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h2 class="font-black text-2xl text-gray-900 flex items-center gap-3">
                    <div class="p-2 bg-indigo-100 rounded-xl">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                    Modifier la place
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-b from-gray-50 to-white min-h-screen">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-xl p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1">
                            <h3 class="font-bold text-red-900 mb-2">Erreurs detectees</h3>
                            <ul class="space-y-1">
                                @foreach($errors->all() as $error)
                                    <li class="text-sm text-red-700">• {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 p-8 text-white">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black mb-1">Mettre a jour la place</h3>
                            <p class="text-indigo-100">Modifiez le nom, la description et le tarif horaire</p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('places.update', $place->id) }}" class="p-8 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="nom" class="text-sm font-bold text-gray-900 mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            Nom de la place <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="nom"
                            name="nom"
                            value="{{ old('nom', $place->nom) }}"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium text-gray-900"
                            required>
                    </div>

                    <div>
                        <label for="cancel_deadline_hours" class="text-sm font-bold text-gray-900 mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Delai d'annulation <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="cancel_deadline_hours"
                            name="cancel_deadline_hours"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium text-gray-900"
                            required>
                            <option value="12" {{ old('cancel_deadline_hours', (string) ($place->cancel_deadline_hours ?? 12)) === '12' ? 'selected' : '' }}>12 heures avant</option>
                            <option value="24" {{ old('cancel_deadline_hours', (string) ($place->cancel_deadline_hours ?? 12)) === '24' ? 'selected' : '' }}>24 heures avant</option>
                        </select>
                        <p class="mt-2 text-xs text-gray-500">Les locataires pourront annuler jusqu'a ce delai.</p>
                    </div>

                    <div>
                        <label for="caracteristiques" class="text-sm font-bold text-gray-900 mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Description
                        </label>
                        <textarea
                            id="caracteristiques"
                            name="caracteristiques"
                            rows="4"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium text-gray-900 resize-none">{{ old('caracteristiques', $place->caracteristiques) }}</textarea>
                    </div>

                    <div>
                        <label for="hourly_price" class="text-sm font-bold text-gray-900 mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Tarif horaire (CHF) <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-stretch rounded-xl border border-gray-200 bg-gray-50 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 transition-all">
                            <span class="inline-flex items-center px-4 text-gray-400 font-bold text-sm border-r border-gray-200">CHF</span>
                            <input
                                type="number"
                                id="hourly_price"
                                name="hourly_price"
                                value="{{ old('hourly_price', number_format(($place->hourly_price_cents ?? 0) / 100, 2, '.', '')) }}"
                                min="0"
                                step="0.50"
                                class="w-full px-4 py-3 bg-transparent focus:ring-0 focus:border-transparent font-medium text-gray-900"
                                required>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-xs">
                            <p class="text-gray-500">Prix par heure de stationnement</p>
                            <button type="button" onclick="document.getElementById('hourly_price').value='0.00'" class="text-indigo-600 font-semibold hover:text-indigo-500">
                                Gratuit
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                        <a href="{{ route('places.mes') }}" class="flex-1 text-center py-3 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-all">
                            Annuler
                        </a>
                        <button type="submit" class="flex-1 flex items-center justify-center gap-2 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
