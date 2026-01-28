@extends('layouts.app')
@section('content')
        <table class="table">
            <thead>
                <tr>
                    <th>Adresse</th>
                    <th>Utilisateur</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Statut</th>
                    <th>Paiement</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reservations as $reservation)
                    <tr>
                        <td>{{ $reservation->place->adresse ?? 'N/A' }}</td>
                        <td>{{ $reservation->user->name ?? 'N/A' }}</td>
                        <td>{{ $reservation->date_debut }}</td>
                        <td>{{ $reservation->date_fin }}</td>
                        <td>{{ $reservation->statut }}</td>
                        <td>
                            @if($reservation->paiement_effectue)
                                <span class="badge bg-success">Payé</span>
                            @else
                                <span class="badge bg-secondary">Non payé</span>
                            @endif
                        </td>
                        <td>
                            @if($reservation->user_id === auth()->id() && $reservation->statut !== 'annulée')
                                <form method="POST" action="{{ route('reservations.destroy', $reservation->id) }}" onsubmit="return confirm('Annuler cette réservation ?');" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <x-danger-button class="px-3 py-1 text-sm">Annuler</x-danger-button>
                                </form>
                            @endif

                            @if(isset($reservation->place->site) && $reservation->place->site->user_id === auth()->id() && $reservation->statut === 'en_attente')
                                <form method="POST" action="{{ route('reservations.valider', $reservation->id) }}" style="display:inline-block;">
                                    @csrf
                                    <x-success-button class="px-3 py-1 text-sm">Valider</x-success-button>
                                </form>
                                <form method="POST" action="{{ route('reservations.refuser', $reservation->id) }}" style="display:inline-block;">
                                    @csrf
                                    <x-warning-button class="px-3 py-1 text-sm">Refuser</x-warning-button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
