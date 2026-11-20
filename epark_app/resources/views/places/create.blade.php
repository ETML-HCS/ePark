<x-app-layout>
    <div class="booking-page max-w-3xl">
        <a href="{{ route('places.mes') }}" class="ep-back-link">
            <span aria-hidden="true">←</span> Retour à mes places
        </a>

        <div class="booking-heading">
            <div>
                <h1>Ajouter une place</h1>
                <p>Renseignez le site, le tarif horaire et les options d’accès de votre emplacement.</p>
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
                price: '{{ old('hourly_price', '2.00') }}',
                cancelDeadline: '{{ old('cancel_deadline_hours', '24') }}',
                characteristics: @js(old('caracteristiques', '')),
                isGroupReserved: {{ old('is_group_reserved', '0') == '1' ? 'true' : 'false' }},
                groupSource: '{{ old('group_source', !empty($savedGroupEntries ?? []) ? 'existing' : 'manual') }}'
            }">
                @csrf
                <input type="hidden" name="create_new_site" :value="newSite ? '1' : '0'">

                {{-- Site --}}
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-sm font-semibold">Site de parking <span class="text-rose-700">*</span></span>
                        <div class="inline-flex rounded-lg border border-[#dce3ed] p-1 bg-[#f3f6fa]">
                            <button type="button" @click="newSite = false" :class="!newSite ? 'bg-white text-[#4338ca] shadow-sm' : 'text-slate-600'" class="px-3 py-1.5 rounded-md text-xs font-semibold">Site existant</button>
                            <button type="button" @click="newSite = true" :class="newSite ? 'bg-white text-[#4338ca] shadow-sm' : 'text-slate-600'" class="px-3 py-1.5 rounded-md text-xs font-semibold">Nouveau site</button>
                        </div>
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

                    <div x-show="newSite" x-cloak class="space-y-3 p-4 rounded-xl bg-[#f3f6fa] border border-[#dce3ed]">
                        <div>
                            <label for="site_nom" class="block text-sm font-semibold mb-1.5">Nom du site</label>
                            <input type="text" id="site_nom" name="site_nom" value="{{ old('site_nom') }}" placeholder="Ex. : Parking Centre-Ville" class="ep-input" :required="newSite" :disabled="!newSite">
                        </div>
                        <div>
                            <label for="site_adresse" class="block text-sm font-semibold mb-1.5">Adresse</label>
                            <input type="text" id="site_adresse" name="site_adresse" value="{{ old('site_adresse') }}" placeholder="Ex. : Rue du Parking 12, 1000 Lausanne" class="ep-input" :required="newSite" :disabled="!newSite">
                        </div>
                    </div>
                </div>

                {{-- Nom de la place --}}
                <div>
                    <label for="nom" class="block text-sm font-semibold mb-1.5">Nom de la place <span class="text-rose-700">*</span></label>
                    <input type="text" id="nom" name="nom" value="{{ old('nom') }}" placeholder="Ex. : Place A12, Emplacement 5…" class="ep-input" required>
                    <p class="mt-1.5 text-xs text-slate-600">Identifiant visible par les locataires lors de la réservation.</p>
                </div>

                {{-- Caractéristiques --}}
                <div>
                    <label for="caracteristiques" class="block text-sm font-semibold mb-1.5">Caractéristiques <span class="text-xs font-normal text-slate-500">(optionnel)</span></label>
                    <textarea id="caracteristiques" name="caracteristiques" rows="3" x-model="characteristics" placeholder="Ex. : Place couverte, accès par badge, borne électrique…" class="ep-input"></textarea>
                    <div class="mt-2.5 flex flex-wrap gap-2">
                        @foreach(['Accès PMR', 'Place couverte', 'Sécurisée (vidéo)', 'Accès par badge'] as $tag)
                            <button type="button" @click="characteristics = (characteristics ? characteristics + ', ' : '') + '{{ $tag }}'" class="px-3 py-1.5 rounded-lg border border-[#dce3ed] bg-white text-xs font-semibold text-slate-700 hover:bg-[#eef2ff] hover:border-[#4338ca]">
                                + {{ $tag }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Tarif horaire --}}
                <div>
                    <label for="hourly_price" class="block text-sm font-semibold mb-1.5">Tarif horaire (CHF) <span class="text-rose-700">*</span></label>
                    <div class="grid gap-3 sm:grid-cols-2 items-start">
                        <div class="relative">
                            <input type="number" id="hourly_price" name="hourly_price" x-model="price" min="0" step="0.05" class="ep-input pr-14 font-bold text-lg" required>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-500">CHF/h</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach(['0.00' => 'Gratuit', '1.00' => '1.00', '2.00' => '2.00', '2.50' => '2.50', '3.00' => '3.00', '3.50' => '3.50'] as $val => $label)
                                <button type="button" @click="price = '{{ $val }}'" :class="price === '{{ $val }}' ? 'bg-[#4338ca] text-white border-[#4338ca]' : 'bg-white text-slate-700 border-[#dce3ed]'" class="px-3 py-2 rounded-lg border text-xs font-semibold">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Délai d'annulation --}}
                <div>
                    <span class="block text-sm font-semibold mb-2">Délai d’annulation <span class="text-rose-700">*</span></span>
                    <input type="hidden" name="cancel_deadline_hours" x-model="cancelDeadline">
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" @click="cancelDeadline = '12'" :aria-pressed="cancelDeadline === '12'" :class="cancelDeadline === '12' ? 'border-[#4338ca] bg-[#eef2ff] text-[#312e81]' : 'border-[#dce3ed] bg-white'" class="p-3.5 rounded-xl border text-left">
                            <strong class="block text-sm">12 heures</strong>
                            <span class="text-xs text-slate-600">Avant le début du créneau</span>
                        </button>
                        <button type="button" @click="cancelDeadline = '24'" :aria-pressed="cancelDeadline === '24'" :class="cancelDeadline === '24' ? 'border-[#4338ca] bg-[#eef2ff] text-[#312e81]' : 'border-[#dce3ed] bg-white'" class="p-3.5 rounded-xl border text-left">
                            <strong class="block text-sm">24 heures</strong>
                            <span class="text-xs text-slate-600">Avant le début du créneau</span>
                        </button>
                    </div>
                </div>

                {{-- Groupe privé --}}
                <div class="pt-4 border-t border-[#dce3ed] space-y-3">
                    <input type="hidden" name="is_group_reserved" value="0">
                    <label class="inline-flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="is_group_reserved" value="1" x-model="isGroupReserved" class="rounded border-slate-300 text-[#4338ca] focus:ring-[#4338ca]">
                        <span class="text-sm font-semibold">Réserver cette place à un groupe privé</span>
                    </label>

                    <div x-show="isGroupReserved" x-cloak class="space-y-4 p-4 rounded-xl bg-[#f3f6fa] border border-[#dce3ed]">
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="groupSource='existing'" :class="groupSource==='existing' ? 'bg-[#4338ca] text-white' : 'bg-white text-slate-700 border border-[#dce3ed]'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">Depuis mes groupes</button>
                            <button type="button" @click="groupSource='manual'" :class="groupSource==='manual' ? 'bg-[#4338ca] text-white' : 'bg-white text-slate-700 border border-[#dce3ed]'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">Nouveau groupe</button>
                        </div>

                        <input type="hidden" name="group_source" :value="groupSource">

                        <div x-show="groupSource==='existing'">
                            <label for="secret_group_index" class="block text-xs font-semibold text-slate-700 mb-1.5">Groupe enregistré</label>
                            <select id="secret_group_index" name="secret_group_index" class="ep-input">
                                <option value="">-- Choisir --</option>
                                @foreach(($savedGroupEntries ?? []) as $idx => $entry)
                                    <option value="{{ $idx }}" @selected((string) old('secret_group_index', '') === (string) $idx)>{{ $entry['name'] !== '' ? $entry['name'] : 'Groupe sans nom' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="groupSource==='manual'" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label for="group_name" class="block text-xs font-semibold text-slate-700 mb-1.5">Nom du groupe</label>
                                <input id="group_name" type="text" name="group_name" value="{{ old('group_name') }}" placeholder="Ex. : ETML/CFPV" class="ep-input">
                            </div>
                            <div>
                                <label for="group_access_code" class="block text-xs font-semibold text-slate-700 mb-1.5">Code d’accès</label>
                                <input id="group_access_code" type="text" name="group_access_code" value="{{ old('group_access_code') }}" placeholder="Min. 4 caractères" class="ep-input">
                            </div>
                        </div>

                        <div>
                            <label for="group_allowed_email_domains_raw" class="block text-xs font-semibold text-slate-700 mb-1.5">Domaines e-mail autorisés (optionnel)</label>
                            <textarea id="group_allowed_email_domains_raw" name="group_allowed_email_domains_raw" rows="2" placeholder="Ex. : eduvaud.ch" class="ep-input">{{ old('group_allowed_email_domains_raw') }}</textarea>
                            <p class="mt-1 text-xs text-slate-600">Un domaine par ligne. Les utilisateurs ayant vérifié leur adresse de ce domaine auront accès au groupe.</p>
                        </div>
                    </div>
                </div>

                <div class="booking-info !m-0">
                    Après création, vous pourrez définir les créneaux bloqués et exceptions depuis « Disponibilités ».
                </div>

                <div class="flex flex-wrap justify-end gap-3 pt-4 border-t border-[#dce3ed]">
                    <a href="{{ route('places.mes') }}" class="booking-secondary">Annuler</a>
                    <button type="submit" class="booking-primary">Créer la place</button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
