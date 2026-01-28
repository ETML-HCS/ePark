<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Liste des sites</h2>
    </x-slot>

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Adresse</th>
                    <th>Propriétaire</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sites as $site)
                    <tr>
                        <td>{{ $site->nom }}</td>
                        <td>{{ $site->adresse }}</td>
                        <td>{{ $site->proprietaire->name ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
