<x-app-layout>
    @php
        $unreadTotal = Auth::user()->unreadNotifications()->count();
    @endphp

    <div class="booking-page max-w-4xl">
        <div class="booking-heading">
            <div>
                <h1>Notifications</h1>
                <p>Suivi de vos demandes, validations, paiements et rappels.</p>
            </div>
            @if($unreadTotal > 0)
                <form method="POST" action="{{ route('notifications.markAllRead') }}">
                    @csrf
                    <button type="submit" class="booking-secondary">Tout marquer comme lu ({{ $unreadTotal }})</button>
                </form>
            @endif
        </div>

        @if($notifications->isEmpty())
            <div class="booking-empty ep-card">
                <h3>Aucune notification</h3>
                <p>Vous serez informé ici dès qu’une réservation est demandée, confirmée, payée ou sur le point de se terminer.</p>
            </div>
        @else
            <div class="ep-card overflow-hidden divide-y divide-[#dce3ed]">
                @foreach($notifications as $notification)
                    @php
                        $payload = $notification->data ?? [];
                        $type = $payload['type'] ?? null;
                        $placeName = $payload['place_name'] ?? '';
                        $isUnread = is_null($notification->read_at);

                        [$title, $detail, $badgeClass] = match ($type) {
                            'end_reminder' => [
                                'Fin de stationnement dans 15 min',
                                $placeName ? 'Pensez à libérer la place '.$placeName.'.' : 'Pensez à libérer votre place à temps.',
                                '',
                            ],
                            'owner_action_required' => [
                                'Demande à valider',
                                $placeName ? 'Nouvelle demande en attente sur '.$placeName.'.' : 'Une demande de réservation attend votre réponse.',
                                '',
                            ],
                            'owner_deadline_reminder' => [
                                'Validation requise sous 90 min',
                                $placeName ? 'Sans réponse pour '.$placeName.', la règle automatique sera appliquée.' : 'Échéance de validation imminente.',
                                '',
                            ],
                            'reservation_status_changed' => [
                                match ($payload['statut'] ?? '') {
                                    'confirmée' => 'Réservation confirmée',
                                    'annulée' => 'Réservation annulée',
                                    default => 'Statut de réservation mis à jour',
                                },
                                trim(($placeName ? $placeName.' · ' : '').($payload['owner_message'] ?? '')),
                                match ($payload['statut'] ?? '') {
                                    'confirmée' => 'is-confirmed',
                                    'annulée' => 'is-cancelled',
                                    default => 'is-neutral',
                                },
                            ],
                            'payment_status_changed' => [
                                match ($payload['status'] ?? '') {
                                    'paid' => 'Paiement déclaré',
                                    'refunded' => 'Remboursement manuel indiqué',
                                    default => 'Statut de paiement mis à jour',
                                },
                                $placeName ?: ('Réservation #'.($payload['reservation_id'] ?? '')),
                                ($payload['status'] ?? '') === 'paid' ? 'is-confirmed' : 'is-neutral',
                            ],
                            'new_reservation' => [
                                'Nouvelle réservation',
                                $placeName,
                                'is-confirmed',
                            ],
                            default => [
                                'Notification',
                                $placeName,
                                'is-neutral',
                            ],
                        };

                        $targetUrl = ! empty($payload['url'])
                            ? url($payload['url'])
                            : (! empty($payload['reservation_id']) ? url('/reservations/'.$payload['reservation_id']) : route('reservations.index'));
                    @endphp

                    <div class="p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4 {{ $isUnread ? 'bg-[#f5f6ff]' : 'bg-white' }}">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="status-pill {{ $badgeClass }}">{{ $title }}</span>
                                @if($isUnread)
                                    <span class="text-xs font-bold text-[#4338ca]">Nouveau</span>
                                @endif
                            </div>
                            @if($detail !== '')
                                <p class="text-sm text-[#18263a] mt-2">{{ $detail }}</p>
                            @endif
                            <p class="text-xs text-slate-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            @if($isUnread)
                                <form method="POST" action="{{ route('notifications.markRead', $notification->id) }}">
                                    @csrf
                                    <button type="submit" class="booking-secondary">Ouvrir</button>
                                </form>
                            @else
                                <a href="{{ $targetUrl }}" class="booking-secondary">Voir</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if(method_exists($notifications, 'links'))
                <div class="mt-6">{{ $notifications->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
