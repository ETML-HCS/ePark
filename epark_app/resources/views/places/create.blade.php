<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ajouter une place</h2>
    </x-slot>

    <div class="container">
        <form method="POST" action="{{ route('places.store') }}">
            @csrf
            <div class="mb-3">
                <label for="site_id" class="form-label">Site</label>
                <select id="site_id" name="site_id" class="form-control" required>
                    <option value="">-- Sélectionner un site --</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->nom }} - {{ $site->adresse }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="nom" class="form-label">Nom</label>
                <input type="text" class="form-control" id="nom" name="nom" required>
            </div>
            <div class="mb-3">
                <label for="caracteristiques" class="form-label">Caractéristiques</label>
                <textarea class="form-control" id="caracteristiques" name="caracteristiques"></textarea>
            </div>
            <x-primary-button>Ajouter</x-primary-button>
        </form>
    </div>
</x-app-layout>
