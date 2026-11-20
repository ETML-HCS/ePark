<x-app-layout>
    <div class="booking-page">
        <div class="booking-heading">
            <div>
                <h1>{{ auth()->user()->isAdmin() ? 'Tous les sites' : 'Mes sites' }}</h1>
                <p>Regroupez vos places de parking par adresse ou bâtiment.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('places.mes') }}" class="booking-secondary">Mes places</a>
                <a href="{{ route('sites.create') }}" class="booking-primary">Nouveau site</a>
            </div>
        </div>

        @if($errors->any())
            <div class="booking-error" role="alert">{{ $errors->first() }}</div>
        @endif

        @if($sites->isEmpty())
            <div class="booking-empty ep-card">
                <h3>Aucun site enregistré</h3>
                <p>Créez un site pour y rattacher vos places de parking.</p>
                <a href="{{ route('sites.create') }}" class="booking-primary mt-4">Créer un site</a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($sites as $site)
                    @php
                        $placesCount = (int) ($site->places_count ?? 0);
                    @endphp
                    <article class="ep-card p-5 flex flex-col">
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="parking-letter" aria-hidden="true">S</span>
                                <h2 class="font-bold text-base truncate">{{ $site->nom }}</h2>
                            </div>
                            <span class="status-pill is-neutral">{{ $placesCount }} {{ $placesCount > 1 ? 'places' : 'place' }}</span>
                        </div>

                        <p class="text-sm text-slate-600">{{ $site->adresse }}</p>
                        @if(auth()->user()->isAdmin())
                            <p class="text-xs text-slate-500 mt-2">Propriétaire : {{ $site->user?->name ?? 'N/A' }}</p>
                        @endif

                        <div class="flex items-center gap-2 mt-auto pt-5 border-t border-[#dce3ed]">
                            <a href="{{ route('sites.edit', $site) }}" class="booking-secondary flex-1 !min-h-[40px] !py-2 !text-xs">Modifier</a>
                            <form method="POST" action="{{ route('sites.destroy', $site) }}" onsubmit="return confirm('Supprimer ce site ?');">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="booking-secondary !min-h-[40px] !py-2 !px-3 !text-xs !text-rose-700 hover:!bg-rose-50"
                                    @disabled($placesCount > 0)
                                    title="{{ $placesCount > 0 ? 'Supprimez d’abord les places rattachées à ce site.' : 'Supprimer ce site' }}">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
