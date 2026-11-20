export default (config) => ({
    config,
    date: config.date,
    places: config.places,
    selectedSiteId: String(config.selectedSiteId || ''),
    selectedPlaceId: String(config.selectedPlaceId || ''),
    selectedSegment: config.selectedSegment,
    vehiclePlate: String(config.vehiclePlate || ''),
    loading: false,
    booking: false,
    loadFailed: false,
    sessionExpired: false,
    error: '',
    success: null,
    requestNumber: 0,
    controller: null,
    init() {
        this.chooseDefaults(true);
        this.$nextTick(() => { this.$refs.siteSelect.value = this.selectedSiteId; });
    },
    get sitePlaces() { return this.places.filter(p => !this.selectedSiteId || String(p.site_id) === this.selectedSiteId); },
    get availablePlaces() { return this.sitePlaces.filter(p => p.availableSegments.includes(this.selectedSegment)); },
    get currentPlace() { return this.availablePlaces.find(p => String(p.id) === this.selectedPlaceId); },
    get formattedDate() { return new Intl.DateTimeFormat('fr-CH', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date(this.date + 'T12:00:00')); },
    money(cents) { return new Intl.NumberFormat('fr-CH', { style: 'currency', currency: 'CHF' }).format((cents || 0) / 100); },
    segmentCount(key) { return this.sitePlaces.filter(p => p.availableSegments.includes(key)).length; },
    chooseDefaults(changeSegment = false) {
        if (!this.config.segments[this.selectedSegment] || (changeSegment && !this.segmentCount(this.selectedSegment))) {
            this.selectedSegment = Object.keys(this.config.segments).find(key => this.segmentCount(key)) || 'matin_travail';
        }
        if (!this.currentPlace) this.selectedPlaceId = String(this.availablePlaces[0]?.id || '');
    },
    setSegment(key) {
        if (this.booking) return;
        this.selectedSegment = key;
        this.chooseDefaults();
    },
    async readResponse(response) {
        if (response.status === 401 || response.status === 419 || response.redirected) {
            this.sessionExpired = true;
            throw new Error('Votre session a expiré. Reconnectez-vous pour continuer.');
        }
        let data;
        try { data = await response.json(); } catch { throw new Error('Le serveur ne répond pas correctement. Réessayez dans un instant.'); }
        if (!response.ok || !data.success) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Impossible de terminer cette action.');
        return data;
    },
    async setDate(value, retry = false) {
        if (this.booking || (!retry && this.date === value)) return;
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value) || value < this.config.today || value > this.config.maxDate) {
            this.error = 'Choisissez une date dans les trois prochaines semaines.';
            return;
        }
        this.controller?.abort();
        this.controller = new AbortController();
        const requestNumber = ++this.requestNumber;
        this.date = value;
        this.loading = true;
        this.error = '';
        this.loadFailed = false;
        try {
            const response = await fetch(this.config.availabilityUrl + '?date=' + encodeURIComponent(value), { headers: { Accept: 'application/json' }, signal: this.controller.signal });
            const data = await this.readResponse(response);
            if (requestNumber !== this.requestNumber) return;
            this.date = data.date;
            this.places = data.places;
            for (const place of data.places) {
                if (!this.config.sites.some(site => String(site.id) === String(place.site_id))) this.config.sites.push({ id: place.site_id, nom: place.site_nom });
            }
            this.chooseDefaults(true);
        } catch (error) {
            if (error.name === 'AbortError' || requestNumber !== this.requestNumber) return;
            this.loadFailed = true;
            this.error = error.message || 'Impossible de charger les disponibilités.';
        } finally {
            if (requestNumber === this.requestNumber) this.loading = false;
        }
    },
    async book() {
        if (this.booking || this.loading || this.loadFailed || !this.currentPlace) return;
        this.booking = true;
        this.error = '';
        try {
            const response = await fetch(this.config.bookUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.config.csrf },
                body: JSON.stringify({
                    place_id: this.selectedPlaceId,
                    date: this.date,
                    segment: this.selectedSegment,
                    vehicle_plate: this.vehiclePlate.trim() || null,
                }),
            });
            this.success = await this.readResponse(response);
            const place = this.currentPlace;
            if (place) place.availableSegments = place.availableSegments.filter(key => key !== this.selectedSegment);
            this.$nextTick(() => { this.$refs.success.focus(); this.$refs.success.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
        } catch (error) {
            this.error = error instanceof TypeError ? 'Connexion interrompue. Vérifiez Mes réservations avant de réessayer.' : error.message;
        } finally { this.booking = false; }
    },
    async newBooking() {
        this.success = null;
        await this.setDate(this.date, true);
    },
});
