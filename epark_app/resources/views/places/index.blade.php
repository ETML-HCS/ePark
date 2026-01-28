<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Liste des places</h2>
    </x-slot>

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <table class="table">
            <thead>
                <tr>
                    <th>Adresse</th>
                    <th>Description</th>
                    <th>Propriétaire</th>
                    <th>Disponible</th>
                </tr>
            </thead>
            <tbody>
                @forelse($places as $place)
                    <tr>
                        <td>{{ $place->adresse ?? $place->nom }}</td>
                        <td>{{ $place->description ?? $place->caracteristiques }}</td>
                        <td>{{ optional($place->user)->name ?? 'N/A' }}</td>
                        <td>{{ $place->disponible ? 'Oui' : 'Non' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Aucune place disponible.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
