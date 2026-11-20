<x-app-layout>
    @php
        $initialTab = 'info';
        if ($errors->secretGroup->any() || in_array(session('status'), ['secret-group-added', 'secret-group-exists', 'secret-group-removed'], true)) {
            $initialTab = 'groups';
        } elseif ($errors->updatePassword->any() || session('status') === 'password-updated') {
            $initialTab = 'security';
        }
    @endphp

    <div
        class="booking-page max-w-4xl"
        x-data="{
            activeTab: '{{ $initialTab }}',
            showDeleteConfirm: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }},
            syncFromHash() {
                const hash = window.location.hash.replace('#', '');
                if (hash === 'secret-groups' || hash === 'groups') this.activeTab = 'groups';
                else if (hash === 'security' || hash === 'password') this.activeTab = 'security';
                else if (hash === 'express') this.activeTab = 'express';
                else if (hash === 'info') this.activeTab = 'info';
            },
            init() {
                this.syncFromHash();
                window.addEventListener('hashchange', () => this.syncFromHash());
            }
        }"
    >
        <div class="booking-heading">
            <div>
                <h1>Mon profil</h1>
                <p>{{ auth()->user()->name }} · {{ auth()->user()->email }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('places.create') }}" class="booking-secondary !min-h-[38px] !py-1.5 !px-3 !text-xs">+ Proposer une place</a>
                @if(auth()->user()->email_verified_at)
                    <span class="status-pill is-confirmed">E-mail vérifié</span>
                @else
                    <span class="status-pill">E-mail non vérifié</span>
                @endif
                @if(auth()->user()->role)
                    <span class="status-pill is-neutral">{{ ucfirst(auth()->user()->role) }}</span>
                @endif
            </div>
        </div>

        {{-- Navigation par onglets --}}
        <div class="ep-card p-1.5 mb-6 grid grid-cols-2 sm:grid-cols-4 gap-1.5" role="tablist" aria-label="Sections du profil">
            <button
                type="button"
                role="tab"
                :aria-selected="activeTab === 'info'"
                @click="activeTab = 'info'"
                :class="activeTab === 'info' ? 'bg-[#eef2ff] text-[#312e81] font-bold' : 'text-slate-600 hover:bg-slate-50 font-semibold'"
                class="px-3 py-2.5 rounded-lg text-sm transition-colors">
                Informations
            </button>
            <button
                type="button"
                role="tab"
                :aria-selected="activeTab === 'groups'"
                @click="activeTab = 'groups'"
                :class="activeTab === 'groups' ? 'bg-[#eef2ff] text-[#312e81] font-bold' : 'text-slate-600 hover:bg-slate-50 font-semibold'"
                class="px-3 py-2.5 rounded-lg text-sm transition-colors">
                Groupes privés
            </button>
            <button
                type="button"
                role="tab"
                :aria-selected="activeTab === 'express'"
                @click="activeTab = 'express'"
                :class="activeTab === 'express' ? 'bg-[#eef2ff] text-[#312e81] font-bold' : 'text-slate-600 hover:bg-slate-50 font-semibold'"
                class="px-3 py-2.5 rounded-lg text-sm transition-colors">
                Accès rapide
            </button>
            <button
                type="button"
                role="tab"
                :aria-selected="activeTab === 'security'"
                @click="activeTab = 'security'"
                :class="activeTab === 'security' ? 'bg-[#eef2ff] text-[#312e81] font-bold' : 'text-slate-600 hover:bg-slate-50 font-semibold'"
                class="px-3 py-2.5 rounded-lg text-sm transition-colors">
                Mot de passe
            </button>
        </div>

        {{-- Section Informations --}}
        <section id="info-section" role="tabpanel" x-show="activeTab === 'info'" class="ep-card p-6 sm:p-8 mb-6">
            <h2 class="text-lg font-bold mb-1">Informations personnelles</h2>
            <p class="text-sm text-slate-600 mb-6">Mettez à jour votre nom et votre adresse e-mail.</p>
            @include('profile.partials.update-profile-information-form')
        </section>

        {{-- Section Groupes privés --}}
        <section id="secret-groups" role="tabpanel" x-show="activeTab === 'groups'" x-cloak class="ep-card p-6 sm:p-8 mb-6">
            <h2 class="text-lg font-bold mb-1">Groupes privés</h2>
            <p class="text-sm text-slate-600 mb-6">Ajoutez un groupe à l’aide de son nom et de son code d’accès pour voir ses places réservées.</p>
            @include('profile.partials.secret-groups-form')
        </section>

        {{-- Section Accès rapide --}}
        <section id="express" role="tabpanel" x-show="activeTab === 'express'" x-cloak class="ep-card p-6 sm:p-8 mb-6">
            @include('profile.partials.express-key-form')
        </section>

        {{-- Section Mot de passe --}}
        <section id="security" role="tabpanel" x-show="activeTab === 'security'" x-cloak class="ep-card p-6 sm:p-8 mb-6">
            <h2 class="text-lg font-bold mb-1">Modifier le mot de passe</h2>
            <p class="text-sm text-slate-600 mb-6">Utilisez un mot de passe long et unique pour sécuriser votre compte.</p>
            @include('profile.partials.update-password-form')
        </section>

        {{-- Zone de danger --}}
        <section class="ep-card border-rose-200 p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-rose-900">Suppression du compte</h2>
                    <p class="text-sm text-slate-600 mt-0.5">Action définitive et irréversible.</p>
                </div>
                <button
                    type="button"
                    @click="showDeleteConfirm = !showDeleteConfirm"
                    class="booking-secondary !border-rose-200 !text-rose-800 hover:!bg-rose-50">
                    <span x-text="showDeleteConfirm ? 'Masquer' : 'Supprimer mon compte'"></span>
                </button>
            </div>

            <div x-show="showDeleteConfirm" x-cloak class="mt-5 pt-5 border-t border-rose-100">
                @include('profile.partials.delete-user-form')
            </div>
        </section>
    </div>
</x-app-layout>
