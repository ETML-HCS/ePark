<x-app-layout>
    <div class="booking-page">
        <div class="booking-heading">
            <div>
                <h1>Mes places</h1>
                <p>Gérez vos emplacements, leurs tarifs et leurs règles de disponibilité.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('sites.index') }}" class="booking-secondary">Mes sites</a>
                <a href="{{ route('places.create') }}" class="booking-primary">Ajouter une place</a>
            </div>
        </div>

        @if($errors->any())
            <div class="booking-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-cols-3 gap-3 mb-8">
            <div class="ep-card p-4">
                <span class="text-xs font-semibold text-slate-600 uppercase">Total places</span>
                <strong class="block text-2xl mt-2">{{ $places->count() }}</strong>
            </div>
            <div class="ep-card p-4">
                <span class="text-xs font-semibold text-slate-600 uppercase">Actives</span>
                <strong class="block text-2xl mt-2 text-[#166534]">{{ $places->where('is_active', true)->count() }}</strong>
            </div>
            <div class="ep-card p-4">
                <span class="text-xs font-semibold text-slate-600 uppercase">Sites</span>
                <strong class="block text-2xl mt-2">{{ $places->pluck('site')->filter()->unique('id')->count() }}</strong>
            </div>
        </div>

        @if($places->isEmpty())
            <div class="booking-empty ep-card">
                <h3>Aucune place pour le moment</h3>
                <p>Ajoutez votre première place de parking pour commencer à recevoir des demandes.</p>
                <a href="{{ route('places.create') }}" class="booking-primary mt-4">Ajouter une place</a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($places as $place)
                    @php
                        $cardDescription = $place->caracteristiques ?: $place->description;
                    @endphp
                    <article class="ep-card overflow-hidden flex flex-col">
                        <div class="px-5 pt-5 pb-4 border-b border-[#dce3ed] bg-[#f8fafc]">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="parking-letter" aria-hidden="true">P</span>
                                    <div class="min-w-0">
                                        <h2 class="font-bold text-base truncate">{{ $place->nom }}</h2>
                                        <p class="text-xs text-slate-600 truncate">{{ optional($place->site)->nom ?? 'Site N/A' }}</p>
                                    </div>
                                </div>
                                <span class="status-pill {{ $place->is_active ? 'is-confirmed' : 'is-neutral' }}">
                                    {{ $place->is_active ? 'Active' : 'En pause' }}
                                </span>
                            </div>

                            @if($place->hasWeeklySchedule())
                                <div class="mt-4 flex justify-center">
                                    @include('places.partials.weekly-visual-calendar', [
                                        'place' => $place,
                                        'heightClass' => 'h-16',
                                        'labelClass' => 'text-slate-600',
                                    ])
                                </div>
                            @endif
                        </div>

                        <div class="p-5 flex-1 flex flex-col">
                            <p class="text-sm text-slate-600">{{ optional($place->site)->adresse ?? 'Adresse non renseignée' }}</p>

                            @if($cardDescription)
                                <p class="text-xs text-slate-500 mt-2 leading-relaxed">{{ Str::limit($cardDescription, 100) }}</p>
                            @endif

                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span class="status-pill is-neutral">{{ format_chf(($place->hourly_price_cents ?? 0) / 100) }}/h</span>
                                @if(($place->feedbacks_count ?? 0) > 0)
                                    <span class="status-pill">★ {{ number_format((float) $place->feedbacks_avg_rating, 1) }} ({{ $place->feedbacks_count }})</span>
                                @endif
                                @if($place->hasWeeklySchedule())
                                    <span class="status-pill is-neutral">{{ $place->weeklyScheduleLabel() }}</span>
                                @endif
                                @if($place->is_group_reserved)
                                    <span class="status-pill">Groupe : {{ $place->group_name ?? 'Privé' }}</span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 mt-auto pt-5 border-t border-[#dce3ed]">
                                <a href="{{ route('places.blocked-slots.edit', $place->id) }}" class="booking-primary flex-1 !min-h-[40px] !py-2 !text-xs">
                                    Disponibilités
                                </a>
                                <a href="{{ route('places.edit', $place->id) }}" class="booking-secondary !min-h-[40px] !py-2 !px-3 !text-xs">
                                    Modifier
                                </a>
                                <form method="POST" action="{{ route('places.destroy', $place->id) }}" onsubmit="return confirm('Supprimer cette place ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="booking-secondary !min-h-[40px] !py-2 !px-3 !text-xs !text-rose-700 hover:!bg-rose-50" aria-label="Supprimer {{ $place->nom }}">
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
