<x-app-layout>
    <div class="booking-page max-w-3xl">
        <div class="booking-heading">
            <div>
                <h1>Bienvenue sur ePark</h1>
                <p>Choisissez votre site favori pour accéder plus rapidement aux places disponibles.</p>
            </div>
        </div>

        @if($errors->any())
            <div class="booking-error" role="alert">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div x-data="{
            action: '{{ old('action', $sites->isNotEmpty() ? 'choose' : 'create') }}',
            selectedSite: '{{ old('site_id', '') }}',
            siteName: @js(old('site_nom', '')),
            siteAddress: @js(old('site_adresse', ''))
        }">
            <form action="{{ route('onboarding.store') }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="action" x-model="action">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <button
                        type="button"
                        @click="action = 'choose'"
                        :aria-pressed="action === 'choose'"
                        :class="action === 'choose' ? 'border-[#4338ca] bg-[#eef2ff] text-[#312e81]' : 'border-[#dce3ed] bg-white'"
                        class="p-5 rounded-xl border text-left transition-colors">
                        <strong class="block text-base">Choisir un site existant</strong>
                        <span class="block text-sm text-slate-600 mt-1">Sélectionnez un parking déjà référencé.</span>
                    </button>

                    <button
                        type="button"
                        @click="action = 'create'"
                        :aria-pressed="action === 'create'"
                        :class="action === 'create' ? 'border-[#4338ca] bg-[#eef2ff] text-[#312e81]' : 'border-[#dce3ed] bg-white'"
                        class="p-5 rounded-xl border text-left transition-colors">
                        <strong class="block text-base">Créer un nouveau site</strong>
                        <span class="block text-sm text-slate-600 mt-1">Ajoutez votre propre emplacement.</span>
                    </button>
                </div>

                {{-- Option A : Choisir un site existant --}}
                <section x-show="action === 'choose'" class="ep-card p-6">
                    <h2 class="text-base font-bold mb-4">Sites disponibles</h2>

                    @if($sites->isNotEmpty())
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-96 overflow-y-auto">
                            @foreach($sites as $site)
                                <label class="cursor-pointer block">
                                    <input type="radio" name="site_id" value="{{ $site->id }}" x-model="selectedSite" class="sr-only">
                                    <div
                                        class="p-4 border rounded-xl transition-colors"
                                        :class="String(selectedSite) === '{{ $site->id }}' ? 'border-[#4338ca] bg-[#eef2ff]' : 'border-[#dce3ed] bg-white hover:bg-slate-50'">
                                        <p class="font-bold text-sm truncate">{{ $site->nom }}</p>
                                        <p class="text-xs text-slate-600 truncate mt-1">{{ $site->adresse }}</p>
                                        @if($site->user)
                                            <p class="text-xs text-[#4338ca] mt-1.5">Par {{ $site->user->name }}</p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="booking-empty">
                            <h3>Aucun site disponible</h3>
                            <p>Créez le premier site pour démarrer.</p>
                        </div>
                    @endif
                </section>

                {{-- Option B : Créer un nouveau site --}}
                <section x-show="action === 'create'" x-cloak class="ep-card p-6 space-y-4">
                    <h2 class="text-base font-bold">Nouveau site</h2>
                    <div>
                        <label for="site_nom" class="block text-sm font-semibold mb-1.5">Nom du site <span class="text-rose-700">*</span></label>
                        <input type="text" id="site_nom" name="site_nom" x-model="siteName" placeholder="Ex. : Parking Centre-Ville" class="ep-input">
                    </div>
                    <div>
                        <label for="site_adresse" class="block text-sm font-semibold mb-1.5">Adresse <span class="text-rose-700">*</span></label>
                        <input type="text" id="site_adresse" name="site_adresse" x-model="siteAddress" placeholder="Ex. : 12 Rue de la Gare, 1000 Lausanne" class="ep-input">
                    </div>
                </section>

                <button
                    type="submit"
                    class="booking-primary w-full"
                    :disabled="(action === 'choose' && !selectedSite) || (action === 'create' && (!siteName || !siteAddress))">
                    Continuer
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
