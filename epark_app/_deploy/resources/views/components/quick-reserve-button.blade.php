@props(['place', 'date' => null])

@php
    $reservationUrl = route('reservations.create', [
        'place_id' => $place->id,
        'site_id' => $place->site_id,
        'date' => $date ?? now()->toDateString(),
    ]);
@endphp

<a 
    href="{{ $reservationUrl }}"
    {{ $attributes->merge([
        'class' => 'inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200 hover:shadow-xl transform hover:-translate-y-0.5'
    ]) }}
>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
    </svg>
    {{ $slot->isEmpty() ? 'Réserver' : $slot }}
</a>
