<x-app-layout>
    <div class="booking-page max-w-2xl">
        <a href="{{ route('sites.index') }}" class="ep-back-link">
            <span aria-hidden="true">←</span> Retour à mes sites
        </a>

        <div class="booking-heading">
            <div>
                <h1>Créer un site</h1>
                <p>Renseignez le nom, l’adresse et éventuellement la position sur la carte.</p>
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

        <section class="ep-card p-6 sm:p-8" x-data="sitePickerMap(@js(old('latitude', '')), @js(old('longitude', '')))">
            <form method="POST" action="{{ route('sites.store') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="nom" class="block text-sm font-semibold mb-1.5">Nom du site <span class="text-rose-700">*</span></label>
                    <input id="nom" name="nom" type="text" class="ep-input" required autofocus value="{{ old('nom') }}" placeholder="Ex. : Parking Gare Centrale">
                </div>
                <div>
                    <label for="adresse" class="block text-sm font-semibold mb-1.5">Adresse <span class="text-rose-700">*</span></label>
                    <input id="adresse" name="adresse" type="text" class="ep-input" required value="{{ old('adresse') }}" placeholder="Ex. : Rue de la Gare 12, 1000 Lausanne">
                </div>

                <div class="pt-4 border-t border-[#dce3ed] space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <span class="block text-sm font-semibold">Position sur la carte <span class="text-xs font-normal text-slate-500">(facultatif)</span></span>
                            <p class="text-xs text-slate-600 mt-0.5">Cliquez sur la carte pour positionner le parking afin qu’il apparaisse sur la carte interactive.</p>
                        </div>
                        <button type="button" x-show="latitude !== '' || longitude !== ''" x-cloak @click="clearCoords()" class="text-link text-xs">Effacer</button>
                    </div>
                    <div x-ref="pickerContainer" class="ep-map-picker" aria-label="Sélectionner la position du site sur la carte"></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="latitude" class="block text-xs font-semibold text-slate-600 mb-1">Latitude</label>
                            <input id="latitude" name="latitude" type="number" step="0.000001" min="-90" max="90" x-model="latitude" class="ep-input" placeholder="46.519653">
                        </div>
                        <div>
                            <label for="longitude" class="block text-xs font-semibold text-slate-600 mb-1">Longitude</label>
                            <input id="longitude" name="longitude" type="number" step="0.000001" min="-180" max="180" x-model="longitude" class="ep-input" placeholder="6.632273">
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-3 pt-4 border-t border-[#dce3ed]">
                    <a href="{{ route('sites.index') }}" class="booking-secondary">Annuler</a>
                    <button type="submit" class="booking-primary">Créer le site</button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
