<x-app-layout>
    @php
        $openAdvanced = old('caracteristiques') !== null
            || old('is_group_reserved') == '1'
            || old('cancel_deadline_hours') === '12'
            || $errors->hasAny(['caracteristiques', 'cancel_deadline_hours', 'group_name', 'group_access_code', 'secret_group_index', 'group_allowed_email_domains_raw']);
    @endphp
    <div class="booking-page max-w-3xl">
        <a href="{{ route('places.mes') }}" class="ep-back-link">
            <span aria-hidden="true">←</span> Retour à mes places
        </a>

        <div class="booking-heading">
            <div>
                <h1>Ajouter une place</h1>
                <p>Indiquez où se trouve votre place et définissez son tarif en quelques clics.</p>
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

        <section class="ep-card p-6 sm:p-8">
            <form method="POST" action="{{ route('places.store') }}" class="space-y-6" x-data="{
                newSite: {{ $sites->isEmpty() || old('create_new_site') == '1' ? 'true' : 'false' }},
                cancelDeadline: '{{ old('cancel_deadline_hours', '24') }}',
                characteristics: @js(old('caracteristiques', '')),
                isGroupReserved: {{ old('is_group_reserved', '0') == '1' ? 'true' : 'false' }},
                groupSource: '{{ old('group_source', !empty($savedGroupEntries ?? []) ? 'existing' : 'manual') }}'
            }">
                @csrf
                <input type="hidden" name="create_new_site" :value="newSite ? '1' : '0'">

                {{-- 1. Lieu : Site + Nom --}}
                <div class="space-y-4">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm font-semibold">Site de parking <span class="text-rose-700">*</span></span>
                            @if($sites->isNotEmpty())
                                <div class="inline-flex rounded-lg border border-[#dce3ed] p-1 bg-[#f3f6fa]">
                                    <button type="button" @click="newSite = false" :class="!newSite ? 'bg-white text-[#4338ca] shadow-sm' : 'text-slate-600'" class="px-3 py-1 rounded-md text-xs font-semibold">Site existant</button>
                                    <button type="button" @click="newSite = true" :class="newSite ? 'bg-white text-[#4338ca] shadow-sm' : 'text-slate-600'" class="px-3 py-1 rounded-md text-xs font-semibold">Nouveau site</button>
                                </div>
                            @endif
                        </div>

                        <div x-show="!newSite">
                            <label for="site_id" class="sr-only">Sélectionner un site</label>
                            <select id="site_id" name="site_id" class="ep-input" :required="!newSite" :disabled="newSite">
                                <option value="">-- Sélectionner un site --</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" @selected((string) old('site_id', $selectedSiteId ?? '') === (string) $site->id)>
                                        {{ $site->nom }} — {{ $site->adresse }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="newSite" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 rounded-xl bg-[#f3f6fa] border border-[#dce3ed]">
                            <div>
                                <label for="site_nom" class="block text-xs font-semibold mb-1">Nom du site</label>
                                <input type="text" id="site_nom" name="site_nom" value="{{ old('site_nom') }}" placeholder="Ex. : Parking Gare" class="ep-input" :required="newSite" :disabled="!newSite">
                            </div>
                            <div>
                                <label for="site_adresse" class="block text-xs font-semibold mb-1">Adresse</label>
                                <input type="text" id="site_adresse" name="site_adresse" value="{{ old('site_adresse') }}" placeholder="Ex. : Rue du Parking 12, Lausanne" class="ep-input" :required="newSite" :disabled="!newSite">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="nom" class="block text-sm font-semibold mb-1.5">Nom ou numéro de la place <span class="text-rose-700">*</span></label>
                        <input type="text" id="nom" name="nom" value="{{ old('nom') }}" placeholder="Ex. : Place A12, Emplacement 5…" class="ep-input" required>
                    </div>
                </div>

                {{-- 2. Tarification & éditeur visuel direct --}}
                @include('places.partials.rental-rules-editor')

                {{-- 3. Options avancées (repliées par défaut) --}}
                <details class="rounded-xl border border-[#dce3ed] bg-[#f8fafc]" @if($openAdvanced) open @endif>
                    <summary class="cursor-pointer list-none px-4 py-3.5 flex items-center justify-between gap-2 text-sm font-semibold text-[#18263a] hover:bg-slate-100/70 rounded-xl">
                        <span>
                            Options avancées
                            <span class="font-normal text-xs text-slate-500 ml-1">· Caractéristiques, annulation (<span x-text="cancelDeadline"></span>h), groupe privé</span>
                        </span>
                        <span aria-hidden="true" class="text-slate-400 text-xs">▾</span>
                    </summary>

                    <div class="px-4 pb-5 pt-2 space-y-5 border-t border-[#dce3ed]">
                        {{-- Caractéristiques --}}
                        <div>
                            <label for="caracteristiques" class="block text-xs font-semibold text-slate-700 mb-1.5">Caractéristiques (optionnel)</label>
                            <textarea id="caracteristiques" name="caracteristiques" rows="2" x-model="characteristics" placeholder="Ex. : Place couverte, accès par badge, borne électrique…" class="ep-input"></textarea>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach(['Accès PMR', 'Place couverte', 'Sécurisée (vidéo)', 'Accès par badge'] as $tag)
                                    <button type="button" @click="characteristics = (characteristics ? characteristics + ', ' : '') + '{{ $tag }}'" class="px-2.5 py-1 rounded-lg border border-[#dce3ed] bg-white text-xs font-semibold text-slate-700 hover:bg-[#eef2ff] hover:border-[#4338ca]">
                                        + {{ $tag }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Délai d'annulation --}}
                        <div>
                            <span class="block text-xs font-semibold text-slate-700 mb-1.5">Délai d’annulation par le locataire</span>
                            <input type="hidden" name="cancel_deadline_hours" x-model="cancelDeadline">
                            <div class="inline-flex rounded-lg border border-[#dce3ed] p-1 bg-white">
                                <button type="button" @click="cancelDeadline = '12'" :aria-pressed="cancelDeadline === '12'" :class="cancelDeadline === '12' ? 'bg-[#4338ca] text-white' : 'text-slate-700'" class="px-3 py-1.5 rounded-md text-xs font-semibold">12 heures avant</button>
                                <button type="button" @click="cancelDeadline = '24'" :aria-pressed="cancelDeadline === '24'" :class="cancelDeadline === '24' ? 'bg-[#4338ca] text-white' : 'text-slate-700'" class="px-3 py-1.5 rounded-md text-xs font-semibold">24 heures avant</button>
                            </div>
                        </div>

                        {{-- Groupe privé --}}
                        <div class="space-y-3 pt-2 border-t border-[#dce3ed]">
                            <input type="hidden" name="is_group_reserved" value="0">
                            <label class="inline-flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="is_group_reserved" value="1" x-model="isGroupReserved" class="rounded border-slate-300 text-[#4338ca] focus:ring-[#4338ca]">
                                <span class="text-xs font-semibold text-slate-800">Réserver cette place à un groupe privé</span>
                            </label>

                            <div x-show="isGroupReserved" x-cloak class="space-y-3 p-3.5 rounded-xl bg-white border border-[#dce3ed]">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="groupSource='existing'" :class="groupSource==='existing' ? 'bg-[#4338ca] text-white' : 'bg-[#f8fafc] text-slate-700 border border-[#dce3ed]'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">Depuis mes groupes</button>
                                    <button type="button" @click="groupSource='manual'" :class="groupSource==='manual' ? 'bg-[#4338ca] text-white' : 'bg-[#f8fafc] text-slate-700 border border-[#dce3ed]'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">Nouveau groupe</button>
                                </div>

                                <input type="hidden" name="group_source" :value="groupSource">

                                <div x-show="groupSource==='existing'">
                                    <label for="secret_group_index" class="block text-xs font-semibold text-slate-700 mb-1">Groupe enregistré</label>
                                    <select id="secret_group_index" name="secret_group_index" class="ep-input">
                                        <option value="">-- Choisir --</option>
                                        @foreach(($savedGroupEntries ?? []) as $idx => $entry)
                                            <option value="{{ $idx }}" @selected((string) old('secret_group_index', '') === (string) $idx)>{{ $entry['name'] !== '' ? $entry['name'] : 'Groupe sans nom' }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div x-show="groupSource==='manual'" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label for="group_name" class="block text-xs font-semibold text-slate-700 mb-1">Nom du groupe</label>
                                        <input id="group_name" type="text" name="group_name" value="{{ old('group_name') }}" placeholder="Ex. : ETML/CFPV" class="ep-input">
                                    </div>
                                    <div>
                                        <label for="group_access_code" class="block text-xs font-semibold text-slate-700 mb-1">Code d’accès</label>
                                        <input id="group_access_code" type="text" name="group_access_code" value="{{ old('group_access_code') }}" placeholder="Min. 4 caractères" class="ep-input">
                                    </div>
                                </div>

                                <div>
                                    <label for="group_allowed_email_domains_raw" class="block text-xs font-semibold text-slate-700 mb-1">Domaines e-mail autorisés (optionnel)</label>
                                    <textarea id="group_allowed_email_domains_raw" name="group_allowed_email_domains_raw" rows="2" placeholder="Ex. : eduvaud.ch" class="ep-input">{{ old('group_allowed_email_domains_raw') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </details>

                <div class="flex flex-wrap justify-end gap-3 pt-4 border-t border-[#dce3ed]">
                    <a href="{{ route('places.mes') }}" class="booking-secondary">Annuler</a>
                    <button type="submit" class="booking-primary">Créer la place</button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
