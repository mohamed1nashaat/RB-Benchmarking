import '../css/app.css';
import jQuery from 'jquery';
import select2 from 'select2';

// Make jQuery available globally
window.$ = window.jQuery = jQuery;

// Initialize Select2 with jQuery
select2(jQuery);

// Chart.js global setup
import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    DoughnutController,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';

ChartJS.register(
    ArcElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    LineController,
    BarController,
    DoughnutController,
    Title,
    Tooltip,
    Legend,
    Filler
);

// Make Chart.js available globally for Alpine.js components
window.Chart = ChartJS;

// Alpine.js Select2 components — registered via window so Livewire/Alpine can pick them up
document.addEventListener('alpine:init', () => {
    // Single-select component
    Alpine.data('select2Component', (config) => ({
        wireModel: config.wireModel || null,
        wireChange: config.wireChange || null,
        alpineKey: config.alpineKey || null,
        initialValue: config.initialValue || null,
        placeholder: config.placeholder || '',
        allowClear: config.allowClear !== undefined ? config.allowClear : true,
        searchable: config.searchable !== undefined ? config.searchable : true,

        init() {
            const $el = jQuery(this.$refs.select);
            const component = this;

            const initSelect2 = () => {
                if ($el.data('select2')) {
                    $el.select2('destroy');
                }
                const select2Config = {
                    allowClear: this.allowClear,
                    minimumResultsForSearch: this.searchable ? 0 : Infinity,
                    width: '100%',
                    dropdownAutoWidth: false,
                    dir: document.documentElement.dir || 'ltr',
                };
                if (this.placeholder) {
                    select2Config.placeholder = this.placeholder;
                }
                $el.select2(select2Config);

                // Restore value from Livewire / Alpine parent / initialValue
                let val = null;
                if (this.alpineKey && this.$parent) {
                    val = this.$parent.columnFilters?.[this.alpineKey] ?? '';
                } else if (this.wireModel) {
                    val = this.$wire.get(this.wireModel);
                } else if (this.initialValue !== null && this.initialValue !== '') {
                    val = this.initialValue;
                }
                if (val !== undefined && val !== null) {
                    $el.val(val).trigger('change.select2');
                }
            };

            initSelect2();

            // Watch for Livewire updating <option> elements and rebuild Select2
            const observer = new MutationObserver(() => {
                const currentVal = $el.val();
                initSelect2();
                $el.val(currentVal).trigger('change.select2');
            });
            observer.observe(this.$refs.select, { childList: true, subtree: true });

            // Listen for global clear event
            this._clearHandler = () => {
                const $first = $el.find('option').first();
                if ($first.length && $first.val() === '') {
                    $first.prop('selected', true);
                } else {
                    $el.val(null);
                }
                $el.trigger('change.select2');
            };
            window.addEventListener('benchmark-clear-filters', this._clearHandler);

            // Prevent programmatic updates from re-firing the change handler
            let suppressChange = false;

            // Sync Select2 → Livewire / Alpine parent
            $el.on('change', function () {
                if (suppressChange) return;
                const val = $el.val();
                if (component.alpineKey && component.$parent) {
                    component.$parent.columnFilters[component.alpineKey] = val === null ? '' : val;
                }
                if (component.wireChange) {
                    const match = component.wireChange.match(/^(\w+)\((.+)\)$/);
                    if (match) {
                        const method = match[1];
                        const args = match[2].split(',').map(a => a.trim());
                        component.$wire.call(method, ...args, val === null ? '' : val);
                    } else {
                        component.$wire.call(component.wireChange, val === null ? '' : val);
                    }
                } else if (component.wireModel) {
                    component.$wire.set(component.wireModel, val === null ? '' : val);
                }
            });

            // Watch Livewire → Select2 (value changes only)
            if (this.wireModel) {
                this.$watch(() => this.$wire.get(this.wireModel), (newVal) => {
                    const target = newVal ?? '';
                    if ($el.val() !== target) {
                        suppressChange = true;
                        $el.val(target).trigger('change');
                        suppressChange = false;
                    }
                });
            }

            // Watch Alpine parent → Select2
            if (this.alpineKey && this.$parent) {
                this.$watch(() => this.$parent.columnFilters?.[this.alpineKey], (newVal) => {
                    const target = newVal ?? '';
                    const current = $el.val() ?? '';
                    if (current !== target) {
                        suppressChange = true;
                        if (target === '') {
                            const $first = $el.find('option').first();
                            if ($first.length && $first.val() === '') {
                                $first.prop('selected', true);
                            } else {
                                $el.val(null);
                            }
                        } else {
                            $el.val(target);
                        }
                        $el.trigger('change.select2');
                        suppressChange = false;
                    }
                });
            }

            this._observer = observer;
        },

        destroy() {
            const $el = jQuery(this.$refs.select);
            if ($el.data('select2')) {
                $el.select2('destroy');
            }
            if (this._observer) {
                this._observer.disconnect();
            }
            if (this._clearHandler) {
                window.removeEventListener('benchmark-clear-filters', this._clearHandler);
            }
        }
    }));

    // Multi-select component
    Alpine.data('select2MultiComponent', (config) => ({
        wireModel: config.wireModel,
        placeholder: config.placeholder || '',

        init() {
            const $el = jQuery(this.$refs.select);
            const component = this;

            const initSelect2 = () => {
                if ($el.data('select2')) {
                    $el.select2('destroy');
                }
                $el.select2({
                    placeholder: this.placeholder,
                    allowClear: true,
                    multiple: true,
                    width: '100%',
                    dropdownAutoWidth: false,
                    closeOnSelect: false,
                    dir: document.documentElement.dir || 'ltr',
                });

                // Restore selected values
                const currentVal = this.$wire.get(this.wireModel) || [];
                if (currentVal.length > 0) {
                    $el.val(currentVal).trigger('change.select2');
                }
            };

            initSelect2();

            // Watch for Livewire updating <option> elements and rebuild Select2
            const observer = new MutationObserver(() => {
                const currentVal = $el.val() || [];
                initSelect2();
                $el.val(currentVal).trigger('change.select2');
            });
            observer.observe(this.$refs.select, { childList: true, subtree: true });

            // Handle "All" option selection
            $el.on('select2:select', function (e) {
                if (e.params.data.id === '') {
                    $el.val(null).trigger('change');
                    component.$wire.set(component.wireModel, []);
                }
            });

            // Sync Select2 → Livewire
            $el.on('change', function () {
                const val = $el.val();
                component.$wire.set(component.wireModel, val || []);
            });

            // Watch Livewire → Select2
            this.$watch(() => this.$wire.get(this.wireModel), (newVal) => {
                const current = $el.val() || [];
                const next = newVal || [];
                if (JSON.stringify(current.sort()) !== JSON.stringify([...next].sort())) {
                    $el.val(next).trigger('change.select2');
                }
            });

            this._observer = observer;
        },

        destroy() {
            const $el = jQuery(this.$refs.select);
            if ($el.data('select2')) {
                $el.select2('destroy');
            }
            if (this._observer) {
                this._observer.disconnect();
            }
        }
    }));

    // Resizable table component for benchmarks table
    Alpine.data('resizableTable', () => ({
        widths: {},
        dragging: null,
        startX: 0,
        startWidth: 0,

        loadWidths() {
            try {
                const stored = localStorage.getItem('benchmark-col-widths');
                if (stored) this.widths = JSON.parse(stored);
            } catch(e) {}
        },

        colStyle(key, defaultWidth) {
            const w = this.widths[key];
            return w ? `width: ${w}; min-width: ${w};` : `min-width: ${defaultWidth};`;
        },

        startResize(e, key) {
            this.dragging = key;
            this.startX = e.pageX;
            const th = e.target.closest('th');
            this.startWidth = th.getBoundingClientRect().width;
        },

        onResize(e) {
            if (!this.dragging) return;
            const diff = e.pageX - this.startX;
            this.widths[this.dragging] = Math.max(40, this.startWidth + diff) + 'px';
        },

        stopResize() {
            if (this.dragging) {
                localStorage.setItem('benchmark-col-widths', JSON.stringify(this.widths));
            }
            this.dragging = null;
        },

        resetWidths() {
            this.widths = {};
            localStorage.removeItem('benchmark-col-widths');
        }
    }));

    // Client-side benchmark table management
    Alpine.data('benchmarkTable', (config) => ({
        rows: config.rows,
        filterOptions: config.filterOptions,
        columnFilters: { ...(config.initialFilters || {}) },
        sortField: config.initialSort.field,
        sortDirection: config.initialSort.dir,
        visibleColumns: config.initialVisibleColumns,
        showAllSpend: config.showAllSpend,

        init() {
            // Restore persisted table state from localStorage so it survives
            // Livewire re-renders (main filter changes) and page reloads.
            // NOTE: columnFilters are NOT restored from localStorage — the server
            // provides initialFilters via @js($columnFilters) and must have authority.
            try {
                const saved = localStorage.getItem('benchmark-table-state');
                if (saved) {
                    const state = JSON.parse(saved);
                    if (state.sortField) this.sortField = state.sortField;
                    if (state.sortDirection) this.sortDirection = state.sortDirection;
                    if (state.visibleColumns) this.visibleColumns = state.visibleColumns;
                    if (state.showAllSpend !== undefined) this.showAllSpend = state.showAllSpend;
                }
            } catch (e) { /* ignore corrupt localStorage */ }

            // Persist state whenever it changes
            this.$watch('sortField', () => this.saveState());
            this.$watch('sortDirection', () => this.saveState());
            this.$watch('visibleColumns', () => this.saveState(), { deep: true });
            this.$watch('showAllSpend', () => this.saveState());
        },

        saveState() {
            try {
                localStorage.setItem('benchmark-table-state', JSON.stringify({
                    sortField: this.sortField,
                    sortDirection: this.sortDirection,
                    visibleColumns: this.visibleColumns,
                    showAllSpend: this.showAllSpend,
                }));
            } catch (e) { /* ignore localStorage errors */ }
        },

        get filteredRows() {
            return this.rows.filter(row => {
                for (const [col, val] of Object.entries(this.columnFilters)) {
                    if (!val || val === '') continue;
                    const dataKey = this.mapFilterKey(col);
                    const rowVal = String(row[dataKey] ?? '').toLowerCase();
                    const filterVal = String(val).toLowerCase();
                    if (rowVal !== filterVal) return false;
                }
                return true;
            });
        },

        get sortedRows() {
            const rows = [...this.filteredRows];
            if (!this.sortField) return rows;
            const dataKey = this.mapFilterKey(this.sortField);
            const isText = ['industry', 'category', 'sub_category', 'country', 'funnel_stage', 'objective'].includes(dataKey);
            rows.sort((a, b) => {
                let result;
                if (isText) {
                    result = String(a[dataKey] ?? '').localeCompare(String(b[dataKey] ?? ''));
                } else {
                    result = (a[dataKey] ?? 0) - (b[dataKey] ?? 0);
                }
                return this.sortDirection === 'asc' ? result : -result;
            });
            return rows;
        },

        get hasActiveFilters() {
            return Object.values(this.columnFilters).some(v => v && v !== '');
        },

        sortBy(field) {
            if (this.sortField === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDirection = 'desc';
            }
        },

        isSorted(field) {
            return this.sortField === field;
        },

        isSortAsc() {
            return this.sortDirection === 'asc';
        },

        toggleColumn(key) {
            if (key !== 'industry') {
                this.visibleColumns[key] = !this.visibleColumns[key];
            }
        },

        toggleGroupColumns(group, visible) {
            const groupMap = {
                account: ['country', 'category', 'subCategory'],
                campaign: ['funnelStage', 'campaignObjective'],
                volume: ['spend', 'impressions', 'clicks', 'leads', 'installs', 'conversions'],
                efficiency: ['cpm', 'cpc', 'ctr', 'cpl', 'cpa', 'cpi'],
            };
            for (const col of (groupMap[group] || [])) {
                this.visibleColumns[col] = visible;
            }
        },

        clearFilters() {
            Object.keys(this.columnFilters).forEach(key => {
                this.columnFilters[key] = '';
            });
            // Broadcast to all Select2 instances (old and new) since wire:ignore
            // leaves stale component references after Livewire morphs.
            window.dispatchEvent(new CustomEvent('benchmark-clear-filters'));
        },

        mapFilterKey(bladeKey) {
            const map = {
                subCategory: 'sub_category',
                funnelStage: 'funnel_stage',
                campaignObjective: 'objective',
                spend: 'total_spend',
                impressions: 'total_impressions',
                clicks: 'total_clicks',
                leads: 'total_leads',
                installs: 'total_installs',
                conversions: 'total_conversions',
            };
            return map[bladeKey] || bladeKey;
        },

        getFunnelBadgeClass(stage) {
            const base = 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ';
            if (stage === 'TOF') return base + 'text-blue-600 bg-blue-50';
            if (stage === 'MOF') return base + 'text-amber-600 bg-amber-50';
            if (stage === 'BOF') return base + 'text-green-600 bg-green-50';
            return base + 'text-gray-500 bg-gray-100';
        },

        isColumnVisible(key) {
            if (key === 'industry') return true;
            return this.visibleColumns[key] ?? false;
        },

        getVisibleColumnCount(group) {
            const groupMap = {
                account: ['industry', 'country', 'category', 'subCategory'],
                campaign: ['funnelStage', 'campaignObjective'],
                volume: ['spend', 'impressions', 'clicks', 'leads', 'installs', 'conversions'],
                efficiency: ['cpm', 'cpc', 'ctr', 'cpl', 'cpa', 'cpi'],
            };
            return (groupMap[group] || []).filter(col => this.isColumnVisible(col)).length;
        }
    }));
});
