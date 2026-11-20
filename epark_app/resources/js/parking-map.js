import L from 'leaflet';

const DEFAULT_CENTER = [46.5197, 6.6323]; // Lausanne, Suisse

function escapeHtml(text) {
    return String(text ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function createPinIcon(label = 'P') {
    return L.divIcon({
        className: 'ep-map-pin-wrapper',
        html: `<span class="ep-map-pin">${escapeHtml(label)}</span>`,
        iconSize: [30, 30],
        iconAnchor: [15, 30],
        popupAnchor: [0, -30],
    });
}

export function parkingMap(sites = []) {
    return {
        sites,
        map: null,
        init() {
            if (!this.$refs.mapContainer || !Array.isArray(this.sites) || this.sites.length === 0) {
                return;
            }
            this.$nextTick(() => this.mountMap());
        },
        mountMap() {
            if (this.map) {
                this.map.invalidateSize();
                return;
            }
            const first = this.sites[0];
            this.map = L.map(this.$refs.mapContainer, {
                scrollWheelZoom: false,
            }).setView([first.latitude, first.longitude], 13);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(this.map);

            const bounds = [];
            for (const site of this.sites) {
                const coords = [Number(site.latitude), Number(site.longitude)];
                bounds.push(coords);
                const placesText = site.places_count === 1 ? '1 place' : `${site.places_count} places`;
                const popup = `
                    <div class="ep-map-popup">
                        <strong>${escapeHtml(site.nom)}</strong>
                        <p>${escapeHtml(site.adresse)}</p>
                        <p class="ep-map-popup-meta">${escapeHtml(placesText)} · dès ${escapeHtml(site.min_price_label)}</p>
                        <a href="${escapeHtml(site.url)}">Voir les créneaux →</a>
                    </div>
                `;
                L.marker(coords, { icon: createPinIcon('P') })
                    .addTo(this.map)
                    .bindPopup(popup);
            }

            if (bounds.length > 1) {
                this.map.fitBounds(bounds, { padding: [36, 36], maxZoom: 15 });
            }
        },
        refresh() {
            this.$nextTick(() => {
                if (!this.map) {
                    this.mountMap();
                } else {
                    this.map.invalidateSize();
                }
            });
        },
    };
}

export function sitePickerMap(initialLat = '', initialLng = '') {
    return {
        latitude: initialLat !== null && initialLat !== '' ? String(initialLat) : '',
        longitude: initialLng !== null && initialLng !== '' ? String(initialLng) : '',
        map: null,
        marker: null,
        init() {
            if (!this.$refs.pickerContainer) {
                return;
            }
            this.$nextTick(() => {
                const hasInitial = this.latitude !== '' && this.longitude !== '';
                const center = hasInitial
                    ? [Number(this.latitude), Number(this.longitude)]
                    : DEFAULT_CENTER;

                this.map = L.map(this.$refs.pickerContainer, {
                    scrollWheelZoom: false,
                }).setView(center, hasInitial ? 15 : 11);

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(this.map);

                if (hasInitial) {
                    this.placeMarker(center[0], center[1]);
                }

                this.map.on('click', (event) => {
                    this.setCoords(event.latlng.lat, event.latlng.lng);
                });
            });
        },
        setCoords(lat, lng) {
            this.latitude = Number(lat).toFixed(6);
            this.longitude = Number(lng).toFixed(6);
            this.placeMarker(Number(lat), Number(lng));
        },
        placeMarker(lat, lng) {
            if (!this.map) return;
            if (this.marker) {
                this.marker.setLatLng([lat, lng]);
            } else {
                this.marker = L.marker([lat, lng], { icon: createPinIcon('S') }).addTo(this.map);
            }
        },
        clearCoords() {
            this.latitude = '';
            this.longitude = '';
            if (this.marker && this.map) {
                this.map.removeLayer(this.marker);
                this.marker = null;
            }
        },
    };
}
