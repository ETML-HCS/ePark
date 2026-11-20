export default ({ initialRules = [], defaultPrice = '2.00', maxRules = 5 } = {}) => ({
    maxRules,
    defaultPrice: String(defaultPrice ?? '2.00'),
    pricingMode: Array.isArray(initialRules) && initialRules.length > 0 ? 'rules' : 'single',
    nextUid: 1,
    rules: [],
    dayOptions: [
        { id: 1, short: 'Lun' },
        { id: 2, short: 'Mar' },
        { id: 3, short: 'Mer' },
        { id: 4, short: 'Jeu' },
        { id: 5, short: 'Ven' },
        { id: 6, short: 'Sam' },
        { id: 0, short: 'Dim' },
    ],
    segmentOptions: [
        { key: 'matin_travail', label: 'Matin', hours: '08:00–12:00' },
        { key: 'aprem_travail', label: 'Après-midi', hours: '12:00–17:30' },
        { key: 'journee', label: 'Journée', hours: '08:00–17:30' },
        { key: 'soir', label: 'Soir', hours: '18:00–00:00' },
        { key: 'nuit', label: 'Nuit', hours: '00:00–07:30' },
    ],
    presets: [
        {
            key: 'semaine_journee',
            label: 'Semaine journée',
            badge: 'Gratuit',
            days: [1, 2, 3, 4, 5],
            segments: ['matin_travail', 'aprem_travail', 'journee'],
            hourly_price: '0.00',
        },
        {
            key: 'weekend',
            label: 'Week-end',
            badge: 'Payant',
            days: [6, 0],
            segments: ['matin_travail', 'aprem_travail', 'journee'],
            hourly_price: '2.00',
        },
        {
            key: 'semaine_matin',
            label: 'Matin (sem.)',
            badge: '08h–12h',
            days: [1, 2, 3, 4, 5],
            segments: ['matin_travail'],
            hourly_price: '1.50',
        },
        {
            key: 'semaine_aprem',
            label: 'Après-midi (sem.)',
            badge: '12h–17h30',
            days: [1, 2, 3, 4, 5],
            segments: ['aprem_travail'],
            hourly_price: '1.50',
        },
        {
            key: 'soir',
            label: 'Soirée',
            badge: '18h–00h',
            days: [1, 2, 3, 4, 5, 6, 0],
            segments: ['soir'],
            hourly_price: '1.00',
        },
        {
            key: 'nuit',
            label: 'Nuit',
            badge: '00h–07h30',
            days: [1, 2, 3, 4, 5, 6, 0],
            segments: ['nuit'],
            hourly_price: '1.00',
        },
        {
            key: 'custom',
            label: 'Plage libre',
            badge: 'Perso',
            days: [1, 2, 3, 4, 5],
            segments: ['journee'],
            hourly_price: null,
        },
    ],
    init() {
        if (Array.isArray(initialRules) && initialRules.length > 0) {
            this.rules = initialRules.slice(0, this.maxRules).map((rule) => ({
                uid: this.nextUid++,
                label: String(rule.label ?? ''),
                days: Array.isArray(rule.days) ? rule.days.map((d) => Number(d)) : [],
                segments: Array.isArray(rule.segments) ? rule.segments.map((s) => String(s)) : [],
                hourly_price: rule.hourly_price !== undefined
                    ? Number(rule.hourly_price).toFixed(2)
                    : (rule.hourly_price_cents !== undefined ? (Number(rule.hourly_price_cents) / 100).toFixed(2) : this.defaultPrice),
            }));
        }
    },
    get remainingSlots() {
        return Math.max(0, this.maxRules - this.rules.length);
    },
    get hasOverlap() {
        const seen = new Set();
        for (const rule of this.rules) {
            for (const day of rule.days) {
                for (const seg of rule.segments) {
                    const key = `${day}:${seg}`;
                    if (seen.has(key)) {
                        return true;
                    }
                    seen.add(key);
                }
            }
        }
        return false;
    },
    setPricingMode(mode) {
        this.pricingMode = mode;
        if (mode === 'single') {
            this.rules = [];
        } else if (mode === 'rules' && this.rules.length === 0) {
            this.applyComboSemaineWeekend();
        }
    },
    applyComboSemaineWeekend() {
        this.pricingMode = 'rules';
        this.rules = [
            {
                uid: this.nextUid++,
                label: 'Semaine journée',
                days: [1, 2, 3, 4, 5],
                segments: ['matin_travail', 'aprem_travail', 'journee'],
                hourly_price: '0.00',
            },
            {
                uid: this.nextUid++,
                label: 'Week-end',
                days: [6, 0],
                segments: ['matin_travail', 'aprem_travail', 'journee'],
                hourly_price: this.defaultPrice && Number(this.defaultPrice) > 0 ? Number(this.defaultPrice).toFixed(2) : '2.00',
            },
        ];
    },
    addPreset(preset) {
        if (this.rules.length >= this.maxRules) {
            return;
        }
        this.pricingMode = 'rules';
        const price = preset.hourly_price !== null ? preset.hourly_price : this.defaultPrice;
        this.rules.push({
            uid: this.nextUid++,
            label: preset.key === 'custom' ? `Plage ${this.rules.length + 1}` : preset.label,
            days: [...preset.days],
            segments: [...preset.segments],
            hourly_price: String(price),
        });
    },
    removeRule(index) {
        this.rules.splice(index, 1);
    },
    clearAll() {
        this.rules = [];
        this.pricingMode = 'single';
    },
    toggleDay(rule, dayId) {
        if (rule.days.includes(dayId)) {
            rule.days = rule.days.filter((d) => d !== dayId);
        } else {
            rule.days = [...rule.days, dayId];
        }
    },
    setDayGroup(rule, group) {
        if (group === 'weekdays') {
            rule.days = [1, 2, 3, 4, 5];
        } else if (group === 'weekend') {
            rule.days = [6, 0];
        } else {
            rule.days = [1, 2, 3, 4, 5, 6, 0];
        }
    },
    toggleSegment(rule, segmentKey) {
        if (rule.segments.includes(segmentKey)) {
            rule.segments = rule.segments.filter((s) => s !== segmentKey);
        } else {
            rule.segments = [...rule.segments, segmentKey];
        }
    },
    setRulePrice(rule, value) {
        rule.hourly_price = String(value);
    },
    isFree(rule) {
        return Number(rule.hourly_price || 0) === 0;
    },
    visualBands: [
        { key: 'matin', label: 'Matin', hours: '08h–12h', segments: ['matin_travail', 'journee'] },
        { key: 'aprem', label: 'Ap.-midi', hours: '12h–17h30', segments: ['aprem_travail', 'journee'] },
        { key: 'soir', label: 'Soir', hours: '18h–00h', segments: ['soir'] },
        { key: 'nuit', label: 'Nuit', hours: '00h–07h30', segments: ['nuit'] },
    ],
    paidPalettes: [
        { cell: 'bg-[#4338ca] text-white', dot: 'bg-[#4338ca]' },
        { cell: 'bg-violet-600 text-white', dot: 'bg-violet-600' },
        { cell: 'bg-amber-600 text-white', dot: 'bg-amber-600' },
        { cell: 'bg-sky-700 text-white', dot: 'bg-sky-700' },
        { cell: 'bg-rose-600 text-white', dot: 'bg-rose-600' },
    ],
    get sortedPaidRates() {
        const set = new Set();
        for (const rule of this.rules) {
            if (!rule.days.length || !rule.segments.length) continue;
            const cents = Math.max(0, Math.round(Number(rule.hourly_price || 0) * 100));
            if (cents > 0) set.add(cents);
        }
        return Array.from(set).sort((a, b) => a - b);
    },
    get hasFreeRule() {
        return this.rules.some((rule) => rule.days.length && rule.segments.length && Math.round(Number(rule.hourly_price || 0) * 100) === 0);
    },
    paletteForCents(cents) {
        if (cents === 0) {
            return { cell: 'bg-emerald-500 text-white', dot: 'bg-emerald-500' };
        }
        const idx = this.sortedPaidRates.indexOf(cents);
        return this.paidPalettes[(idx >= 0 ? idx : 0) % this.paidPalettes.length];
    },
    formatCompactCents(cents) {
        if (cents === 0) return '0.-';
        const chf = cents / 100;
        if (Math.abs(chf - Math.round(chf)) < 0.001) {
            return `${Math.round(chf)}.-`;
        }
        return chf.toFixed(2);
    },
    formatFullCents(cents) {
        if (cents === 0) return 'Gratuit';
        return `CHF ${(cents / 100).toFixed(2)}/h`;
    },
    formatDaysSummary(days) {
        const sorted = [...new Set(days)].sort((a, b) => a - b);
        if (sorted.join(',') === '0,1,2,3,4,5,6') return '7j/7';
        if (sorted.join(',') === '1,2,3,4,5') return 'Lun–Ven';
        if (sorted.join(',') === '0,6') return 'Sam–Dim';
        return this.dayOptions.filter((d) => sorted.includes(d.id)).map((d) => d.short).join(', ');
    },
    formatSegmentsSummary(segments) {
        if (segments.includes('journee') && segments.includes('matin_travail') && segments.includes('aprem_travail')) {
            const others = segments.filter((s) => !['journee', 'matin_travail', 'aprem_travail'].includes(s));
            const labels = ['Journée', ...others.map((s) => this.segmentOptions.find((o) => o.key === s)?.label || s)];
            return labels.join(', ');
        }
        return segments.map((s) => this.segmentOptions.find((o) => o.key === s)?.label || s).join(', ');
    },
    get liveSummaries() {
        return this.rules
            .filter((r) => r.days.length && r.segments.length)
            .map((r) => {
                const cents = Math.max(0, Math.round(Number(r.hourly_price || 0) * 100));
                return {
                    uid: r.uid,
                    days: this.formatDaysSummary(r.days),
                    segments: this.formatSegmentsSummary(r.segments),
                    price: this.formatFullCents(cents),
                    dotClass: this.paletteForCents(cents).dot,
                    isFree: cents === 0,
                };
            });
    },
    cellFor(dayId, band) {
        for (const rule of this.rules) {
            if (!rule.days.includes(dayId)) continue;
            const matches = band.segments.some((seg) => rule.segments.includes(seg));
            if (matches) {
                const cents = Math.max(0, Math.round(Number(rule.hourly_price || 0) * 100));
                const palette = this.paletteForCents(cents);
                const dayLabel = this.dayOptions.find((d) => d.id === dayId)?.short || '';
                const labelSuffix = rule.label ? ` (${rule.label})` : '';
                return {
                    open: true,
                    text: this.formatCompactCents(cents),
                    cellClass: palette.cell,
                    title: `${dayLabel} · ${band.label} (${band.hours}) : ${this.formatFullCents(cents)}${labelSuffix}`,
                };
            }
        }
        const dayLabel = this.dayOptions.find((d) => d.id === dayId)?.short || '';
        return {
            open: false,
            text: '',
            cellClass: 'bg-slate-100/70',
            title: `${dayLabel} · ${band.label} (${band.hours}) : Fermé`,
        };
    },
});
