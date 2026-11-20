export default ({ initialRules = [], initialMode = null, defaultPrice = '2.00', maxRules = 5 } = {}) => ({
    maxRules,
    defaultPrice: String(defaultPrice ?? '2.00'),
    pricingMode: initialMode === 'rules' || initialMode === 'single'
        ? initialMode
        : (Array.isArray(initialRules) && initialRules.length > 0 ? 'rules' : 'single'),
    nextBrushId: 1,
    brushes: [],
    activeBrushId: 'eraser',
    grid: {},
    isDragging: false,
    dragValue: null,
    limitWarning: '',
    dayOptions: [
        { id: 1, short: 'Lun' },
        { id: 2, short: 'Mar' },
        { id: 3, short: 'Mer' },
        { id: 4, short: 'Jeu' },
        { id: 5, short: 'Ven' },
        { id: 6, short: 'Sam' },
        { id: 0, short: 'Dim' },
    ],
    bands: [
        { key: 'matin_travail', label: 'Matin', hours: '08h–12h' },
        { key: 'aprem_travail', label: 'Ap.-midi', hours: '12h–17h30' },
        { key: 'soir', label: 'Soir', hours: '18h–00h' },
        { key: 'nuit', label: 'Nuit', hours: '00h–07h30' },
    ],
    paidPalettes: [
        { cell: 'bg-[#4338ca] text-white', dot: 'bg-[#4338ca]', pill: 'border-[#4338ca] bg-[#eef2ff] text-[#312e81]' },
        { cell: 'bg-violet-600 text-white', dot: 'bg-violet-600', pill: 'border-violet-600 bg-violet-50 text-violet-900' },
        { cell: 'bg-amber-600 text-white', dot: 'bg-amber-600', pill: 'border-amber-600 bg-amber-50 text-amber-900' },
        { cell: 'bg-sky-700 text-white', dot: 'bg-sky-700', pill: 'border-sky-700 bg-sky-50 text-sky-900' },
        { cell: 'bg-rose-600 text-white', dot: 'bg-rose-600', pill: 'border-rose-600 bg-rose-50 text-rose-900' },
    ],
    quickZones: [
        { key: 'semaine_journee', label: 'Semaine journée', days: [1, 2, 3, 4, 5], bands: ['matin_travail', 'aprem_travail'] },
        { key: 'weekend_journee', label: 'Week-end', days: [6, 0], bands: ['matin_travail', 'aprem_travail'] },
        { key: 'matin', label: 'Matin (sem.)', days: [1, 2, 3, 4, 5], bands: ['matin_travail'] },
        { key: 'aprem', label: 'Ap.-midi (sem.)', days: [1, 2, 3, 4, 5], bands: ['aprem_travail'] },
        { key: 'soir', label: 'Soir', days: [1, 2, 3, 4, 5, 6, 0], bands: ['soir'] },
        { key: 'nuit', label: 'Nuit', days: [1, 2, 3, 4, 5, 6, 0], bands: ['nuit'] },
    ],
    init() {
        this.grid = this.createEmptyGrid();
        if (Array.isArray(initialRules) && initialRules.length > 0) {
            this.loadRulesIntoGrid(initialRules);
        } else if (this.pricingMode === 'rules') {
            this.applyStarterSemaineWeekend();
        }
    },
    createEmptyGrid() {
        const g = {};
        for (const day of this.dayOptions) {
            g[day.id] = {
                matin_travail: null,
                aprem_travail: null,
                soir: null,
                nuit: null,
            };
        }
        return g;
    },
    cloneGrid(source) {
        const copy = {};
        for (const day of this.dayOptions) {
            copy[day.id] = { ...source[day.id] };
        }
        return copy;
    },
    findOrCreateBrushForCents(cents) {
        const normalized = Math.max(0, Math.round(Number(cents) || 0));
        const existing = this.brushes.find((b) => b.cents === normalized);
        if (existing) {
            return existing;
        }
        const brush = {
            id: `b${this.nextBrushId++}`,
            cents: normalized,
            priceInput: (normalized / 100).toFixed(2),
        };
        this.brushes.push(brush);
        return brush;
    },
    loadRulesIntoGrid(rules) {
        this.brushes = [];
        const g = this.createEmptyGrid();

        for (const rule of rules.slice(0, this.maxRules)) {
            const cents = rule.hourly_price !== undefined && rule.hourly_price !== ''
                ? Math.max(0, Math.round(Number(rule.hourly_price) * 100))
                : Math.max(0, Math.round(Number(rule.hourly_price_cents || 0)));
            const brush = this.findOrCreateBrushForCents(cents);
            const days = Array.isArray(rule.days) ? rule.days.map((d) => Number(d)) : [];
            const segments = Array.isArray(rule.segments) ? rule.segments.map((s) => String(s)) : [];

            const bandsToFill = new Set();
            for (const seg of segments) {
                if (seg === 'journee') {
                    bandsToFill.add('matin_travail');
                    bandsToFill.add('aprem_travail');
                } else if (['matin_travail', 'aprem_travail', 'soir', 'nuit'].includes(seg)) {
                    bandsToFill.add(seg);
                }
            }

            for (const dayId of days) {
                if (!g[dayId]) continue;
                for (const bandKey of bandsToFill) {
                    if (g[dayId][bandKey] === null) {
                        g[dayId][bandKey] = brush.id;
                    }
                }
            }
        }

        this.grid = g;
        this.activeBrushId = this.brushes[0]?.id ?? 'eraser';
    },
    setPricingMode(mode) {
        this.limitWarning = '';
        this.pricingMode = mode;
        if (mode === 'single') {
            this.grid = this.createEmptyGrid();
            this.brushes = [];
            this.activeBrushId = 'eraser';
        } else if (mode === 'rules' && this.compiledRules.length === 0) {
            this.applyStarterSemaineWeekend();
        }
    },
    applyStarterSemaineWeekend() {
        this.limitWarning = '';
        this.pricingMode = 'rules';
        this.brushes = [];
        const paidCents = this.defaultPrice !== '' && Number(this.defaultPrice) > 0
            ? Math.round(Number(this.defaultPrice) * 100)
            : 200;
        const freeBrush = this.findOrCreateBrushForCents(0);
        const paidBrush = this.findOrCreateBrushForCents(paidCents);
        const g = this.createEmptyGrid();
        for (const d of [1, 2, 3, 4, 5]) {
            g[d].matin_travail = freeBrush.id;
            g[d].aprem_travail = freeBrush.id;
        }
        for (const d of [6, 0]) {
            g[d].matin_travail = paidBrush.id;
            g[d].aprem_travail = paidBrush.id;
        }
        this.grid = g;
        this.activeBrushId = paidBrush.id;
    },
    clearGrid() {
        this.limitWarning = '';
        this.grid = this.createEmptyGrid();
    },
    addBrush() {
        if (this.brushes.length >= this.maxRules) {
            return;
        }
        this.limitWarning = '';
        const candidates = [0, 100, 200, 250, 300, 150, 400, 500];
        const nextCents = candidates.find((c) => !this.brushes.some((b) => b.cents === c)) ?? 200;
        const brush = {
            id: `b${this.nextBrushId++}`,
            cents: nextCents,
            priceInput: (nextCents / 100).toFixed(2),
        };
        this.brushes.push(brush);
        this.activeBrushId = brush.id;
    },
    removeBrush(brushId) {
        if (this.brushes.length <= 1) {
            return;
        }
        this.limitWarning = '';
        const g = this.cloneGrid(this.grid);
        for (const day of this.dayOptions) {
            for (const band of this.bands) {
                if (g[day.id][band.key] === brushId) {
                    g[day.id][band.key] = null;
                }
            }
        }
        this.grid = g;
        this.brushes = this.brushes.filter((b) => b.id !== brushId);
        if (this.activeBrushId === brushId) {
            this.activeBrushId = this.brushes[0]?.id ?? 'eraser';
        }
    },
    get activeBrush() {
        return this.brushes.find((b) => b.id === this.activeBrushId) ?? null;
    },
    updateActiveBrushPrice(rawValue) {
        const brush = this.activeBrush;
        if (!brush) return;
        brush.priceInput = String(rawValue);
        if (rawValue === '' || Number.isNaN(Number(rawValue))) {
            return;
        }
        brush.cents = Math.max(0, Math.min(50000, Math.round(Number(rawValue) * 100)));
        this.limitWarning = '';
    },
    setActiveBrushQuickPrice(value) {
        const brush = this.activeBrush;
        if (!brush) return;
        brush.priceInput = String(value);
        brush.cents = Math.max(0, Math.round(Number(value) * 100));
        this.limitWarning = '';
    },
    brushById(id) {
        return this.brushes.find((b) => b.id === id) ?? null;
    },
    get sortedPaidRates() {
        const set = new Set();
        for (const b of this.brushes) {
            if (b.cents > 0) set.add(b.cents);
        }
        return Array.from(set).sort((a, b) => a - b);
    },
    paletteForCents(cents) {
        if (cents === 0) {
            return {
                cell: 'bg-emerald-500 text-white hover:bg-emerald-600',
                dot: 'bg-emerald-500',
                pill: 'border-emerald-600 bg-emerald-50 text-emerald-900',
            };
        }
        const idx = this.sortedPaidRates.indexOf(cents);
        return this.paidPalettes[(idx >= 0 ? idx : 0) % this.paidPalettes.length];
    },
    brushPalette(brush) {
        return this.paletteForCents(brush.cents);
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
        const map = {
            matin_travail: 'Matin',
            aprem_travail: 'Ap.-midi',
            journee: 'Journée',
            soir: 'Soir',
            nuit: 'Nuit',
        };
        if (segments.includes('journee') && segments.includes('matin_travail') && segments.includes('aprem_travail')) {
            const others = segments.filter((s) => !['journee', 'matin_travail', 'aprem_travail'].includes(s));
            return ['Journée', ...others.map((s) => map[s] || s)].join(', ');
        }
        return segments.map((s) => map[s] || s).join(', ');
    },
    compileGridToRules(candidateGrid) {
        // Group days sharing identical (hourly_price_cents, segmentSet) into exact rules
        const groups = new Map();

        for (const day of this.dayOptions) {
            const dayRow = candidateGrid[day.id];
            if (!dayRow) continue;

            // Collect bands per distinct price cents on this day
            const bandsByCents = new Map();
            for (const band of this.bands) {
                const brushId = dayRow[band.key];
                if (!brushId) continue;
                const brush = this.brushById(brushId);
                if (!brush) continue;
                if (!bandsByCents.has(brush.cents)) {
                    bandsByCents.set(brush.cents, []);
                }
                bandsByCents.get(brush.cents).push(band.key);
            }

            for (const [cents, bandKeys] of bandsByCents.entries()) {
                const segments = [...bandKeys];
                if (segments.includes('matin_travail') && segments.includes('aprem_travail')) {
                    segments.push('journee');
                }
                const canonicalOrder = ['matin_travail', 'aprem_travail', 'journee', 'soir', 'nuit'];
                const sortedSegments = canonicalOrder.filter((s) => segments.includes(s));
                const groupKey = `${cents}|${sortedSegments.join(',')}`;

                if (!groups.has(groupKey)) {
                    groups.set(groupKey, {
                        cents,
                        segments: sortedSegments,
                        days: [],
                    });
                }
                groups.get(groupKey).days.push(day.id);
            }
        }

        return Array.from(groups.values()).map((g) => {
            const daysLabel = this.formatDaysSummary(g.days);
            const segLabel = this.formatSegmentsSummary(g.segments);
            return {
                key: `${g.cents}-${g.days.join('_')}-${g.segments.join('_')}`,
                label: `${daysLabel} · ${segLabel}`.slice(0, 60),
                days: g.days,
                segments: g.segments,
                cents: g.cents,
                hourly_price: (g.cents / 100).toFixed(2),
                daysLabel,
                segmentsLabel: segLabel,
                priceLabel: this.formatFullCents(g.cents),
                dotClass: this.paletteForCents(g.cents).dot,
                isFree: g.cents === 0,
            };
        });
    },
    get compiledRules() {
        return this.compileGridToRules(this.grid);
    },
    commitCandidateGrid(candidateGrid) {
        const compiled = this.compileGridToRules(candidateGrid);
        if (compiled.length > this.maxRules) {
            this.limitWarning = `Maximum ${this.maxRules} plages différentes atteint. Regroupez des jours aux mêmes horaires ou effacez une zone.`;
            return false;
        }
        this.limitWarning = '';
        this.grid = candidateGrid;
        return true;
    },
    startPaint(dayId, bandKey) {
        const current = this.grid[dayId]?.[bandKey] ?? null;
        const target = this.activeBrushId === 'eraser'
            ? null
            : (current === this.activeBrushId ? null : this.activeBrushId);
        this.dragValue = target;
        this.isDragging = true;

        const candidate = this.cloneGrid(this.grid);
        candidate[dayId][bandKey] = target;
        this.commitCandidateGrid(candidate);
    },
    continuePaint(dayId, bandKey) {
        if (!this.isDragging) return;
        if ((this.grid[dayId]?.[bandKey] ?? null) === this.dragValue) return;
        const candidate = this.cloneGrid(this.grid);
        candidate[dayId][bandKey] = this.dragValue;
        this.commitCandidateGrid(candidate);
    },
    paintDay(dayId) {
        const candidate = this.cloneGrid(this.grid);
        const target = this.activeBrushId === 'eraser' ? null : this.activeBrushId;
        const allMatch = this.bands.every((b) => candidate[dayId][b.key] === target);
        const nextVal = target !== null && allMatch ? null : target;
        for (const band of this.bands) {
            candidate[dayId][band.key] = nextVal;
        }
        this.commitCandidateGrid(candidate);
    },
    paintBand(bandKey) {
        const candidate = this.cloneGrid(this.grid);
        const target = this.activeBrushId === 'eraser' ? null : this.activeBrushId;
        const allMatch = this.dayOptions.every((d) => candidate[d.id][bandKey] === target);
        const nextVal = target !== null && allMatch ? null : target;
        for (const day of this.dayOptions) {
            candidate[day.id][bandKey] = nextVal;
        }
        this.commitCandidateGrid(candidate);
    },
    applyQuickZone(zone) {
        const candidate = this.cloneGrid(this.grid);
        const target = this.activeBrushId === 'eraser' ? null : this.activeBrushId;
        const allMatch = zone.days.every((d) => zone.bands.every((b) => candidate[d][b] === target));
        const nextVal = target !== null && allMatch ? null : target;
        for (const d of zone.days) {
            for (const b of zone.bands) {
                candidate[d][b] = nextVal;
            }
        }
        this.commitCandidateGrid(candidate);
    },
    cellInfo(dayId, band) {
        const brushId = this.grid[dayId]?.[band.key] ?? null;
        const dayLabel = this.dayOptions.find((d) => d.id === dayId)?.short || '';
        if (!brushId) {
            return {
                text: '',
                cellClass: 'bg-slate-100/80 hover:bg-slate-200/80 border border-dashed border-slate-200',
                title: `${dayLabel} · ${band.label} (${band.hours}) : Fermé — cliquer pour ouvrir`,
            };
        }
        const brush = this.brushById(brushId);
        if (!brush) {
            return { text: '', cellClass: 'bg-slate-100/80', title: '' };
        }
        const palette = this.brushPalette(brush);
        return {
            text: this.formatCompactCents(brush.cents),
            cellClass: `${palette.cell} shadow-sm`,
            title: `${dayLabel} · ${band.label} (${band.hours}) : ${this.formatFullCents(brush.cents)}`,
        };
    },
});
