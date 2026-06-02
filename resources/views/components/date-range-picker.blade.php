@props(['wireModelFrom', 'wireModelTo', 'label' => 'Date Range', 'icon' => 'heroicon-o-calendar-days', 'align' => 'right'])

<div
    x-data="{
        open: false,
        dateFrom: $wire.{{ $wireModelFrom }},
        dateTo: $wire.{{ $wireModelTo }},
        selecting: null,
        hoverDate: null,
        pushing: false,
        leftYear: null,
        leftMonth: null,

        presets: {
            'last_7': { label: '{{ __("Last 7 Days") }}', days: 7 },
            'last_30': { label: '{{ __("Last 30 Days") }}', days: 30 },
            'this_month': { label: '{{ __("This Month") }}' },
            'last_month': { label: '{{ __("Last Month") }}' },
            'this_year': { label: '{{ __("This Year") }}' },
            'all_time': { label: '{{ __("All Time") }}' },
        },
        activePreset: null,

        init() {
            this.syncFromWire();
            $wire.$watch('{{ $wireModelFrom }}', () => this.syncFromWire());
            $wire.$watch('{{ $wireModelTo }}', () => this.syncFromWire());
        },

        syncFromWire() {
            if (this.pushing) return;
            this.dateFrom = $wire.{{ $wireModelFrom }};
            this.dateTo = $wire.{{ $wireModelTo }};
            this.initCalendarMonths();
            this.detectPreset();
        },

        initCalendarMonths() {
            if (this.dateTo) {
                const d = new Date(this.dateTo + 'T00:00:00');
                this.leftYear = d.getFullYear();
                this.leftMonth = d.getMonth() - 1;
                if (this.leftMonth < 0) {
                    this.leftMonth = 11;
                    this.leftYear--;
                }
            } else {
                const now = new Date();
                this.leftYear = now.getFullYear();
                this.leftMonth = now.getMonth() - 1;
                if (this.leftMonth < 0) {
                    this.leftMonth = 11;
                    this.leftYear--;
                }
            }
        },

        get rightYear() {
            return this.leftMonth === 11 ? this.leftYear + 1 : this.leftYear;
        },
        get rightMonth() {
            return this.leftMonth === 11 ? 0 : this.leftMonth + 1;
        },

        prevMonth() {
            this.leftMonth--;
            if (this.leftMonth < 0) { this.leftMonth = 11; this.leftYear--; }
        },
        nextMonth() {
            this.leftMonth++;
            if (this.leftMonth > 11) { this.leftMonth = 0; this.leftYear++; }
        },

        monthName(m) {
            return new Date(2000, m, 1).toLocaleDateString('en-US', { month: 'long' });
        },

        calendarDays(year, month) {
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrev = new Date(year, month, 0).getDate();
            let days = [];

            for (let i = firstDay - 1; i >= 0; i--) {
                days.push({ day: daysInPrev - i, current: false, date: null });
            }
            for (let d = 1; d <= daysInMonth; d++) {
                const mm = String(month + 1).padStart(2, '0');
                const dd = String(d).padStart(2, '0');
                days.push({ day: d, current: true, date: `${year}-${mm}-${dd}` });
            }
            const remaining = 7 - (days.length % 7);
            if (remaining < 7) {
                for (let i = 1; i <= remaining; i++) {
                    days.push({ day: i, current: false, date: null });
                }
            }
            return days;
        },

        selectDate(dateStr) {
            if (!dateStr) return;

            if (!this.selecting) {
                this.selecting = dateStr;
                this.dateFrom = dateStr;
                this.dateTo = dateStr;
            } else {
                let start = this.selecting;
                let end = dateStr;
                if (end < start) { [start, end] = [end, start]; }
                this.dateFrom = start;
                this.dateTo = end;
                this.selecting = null;
                this.hoverDate = null;
                this.pushToWire();
            }
        },

        pushToWire() {
            this.pushing = true;
            $wire.call('setDateRange', this.dateFrom, this.dateTo);
            this.detectPreset();
            this.$nextTick(() => { this.pushing = false; });
        },

        applyPreset(key) {
            const now = new Date();
            let from, to;

            switch(key) {
                case 'last_7':
                    to = new Date(now);
                    from = new Date(now);
                    from.setDate(from.getDate() - 6);
                    break;
                case 'last_30':
                    to = new Date(now);
                    from = new Date(now);
                    from.setDate(from.getDate() - 29);
                    break;
                case 'this_month':
                    from = new Date(now.getFullYear(), now.getMonth(), 1);
                    to = new Date(now);
                    break;
                case 'last_month':
                    from = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                    to = new Date(now.getFullYear(), now.getMonth(), 0);
                    break;
                case 'this_year':
                    from = new Date(now.getFullYear(), 0, 1);
                    to = new Date(now);
                    break;
                case 'all_time':
                    from = new Date(2010, 0, 1);
                    to = new Date(now);
                    break;
            }

            this.dateFrom = this.fmtDate(from);
            this.dateTo = this.fmtDate(to);
            this.selecting = null;
            this.hoverDate = null;
            this.initCalendarMonths();
            this.pushToWire();
            this.open = false;
        },

        clearDates() {
            const now = new Date();
            const from = new Date(2010, 0, 1);
            this.dateFrom = this.fmtDate(from);
            this.dateTo = this.fmtDate(now);
            this.selecting = null;
            this.hoverDate = null;
            this.initCalendarMonths();
            this.pushToWire();
        },

        detectPreset() {
            const now = new Date();
            const todayStr = this.fmtDate(now);

            for (const [key, preset] of Object.entries(this.presets)) {
                let from, to;
                switch(key) {
                    case 'last_7':
                        to = new Date(now); from = new Date(now); from.setDate(from.getDate() - 6);
                        break;
                    case 'last_30':
                        to = new Date(now); from = new Date(now); from.setDate(from.getDate() - 29);
                        break;
                    case 'this_month':
                        from = new Date(now.getFullYear(), now.getMonth(), 1); to = new Date(now);
                        break;
                    case 'last_month':
                        from = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                        to = new Date(now.getFullYear(), now.getMonth(), 0);
                        break;
                    case 'this_year':
                        from = new Date(now.getFullYear(), 0, 1); to = new Date(now);
                        break;
                    case 'all_time':
                        from = new Date(2010, 0, 1); to = new Date(now);
                        break;
                }
                if (this.dateFrom === this.fmtDate(from) && this.dateTo === this.fmtDate(to)) {
                    this.activePreset = key;
                    return;
                }
            }
            this.activePreset = null;
        },

        fmtDate(d) {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        },

        formatDisplay(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        },

        get displayText() {
            if (this.dateFrom && this.dateTo) {
                return this.formatDisplay(this.dateFrom) + ' – ' + this.formatDisplay(this.dateTo);
            }
            return '{{ __("Select date range...") }}';
        },

        isToday(dateStr) {
            return dateStr === this.fmtDate(new Date());
        },

        isStart(dateStr) {
            return dateStr === this.effectiveFrom;
        },
        isEnd(dateStr) {
            return dateStr === this.effectiveTo;
        },

        get effectiveFrom() {
            if (this.selecting && this.hoverDate) {
                return this.selecting <= this.hoverDate ? this.selecting : this.hoverDate;
            }
            return this.dateFrom;
        },
        get effectiveTo() {
            if (this.selecting && this.hoverDate) {
                return this.selecting <= this.hoverDate ? this.hoverDate : this.selecting;
            }
            return this.dateTo;
        },

        isInRange(dateStr) {
            if (!dateStr) return false;
            const from = this.effectiveFrom;
            const to = this.effectiveTo;
            return from && to && dateStr >= from && dateStr <= to;
        },

        dayClasses(dayObj) {
            if (!dayObj.current) return 'text-gray-300 cursor-default';

            const d = dayObj.date;
            let classes = 'cursor-pointer ';

            if (this.isStart(d) || this.isEnd(d)) {
                classes += 'bg-primary-600 text-white font-semibold rounded-md';
            } else if (this.isInRange(d)) {
                classes += 'bg-primary-100 text-primary-800';
            } else if (this.isToday(d)) {
                classes += 'ring-1 ring-primary-400 text-primary-600 rounded-md';
            } else {
                classes += 'text-gray-700 hover:bg-gray-100 rounded-md';
            }

            return classes;
        },

        onHover(dateStr) {
            if (this.selecting && dateStr) {
                this.hoverDate = dateStr;
            }
        }
    }"
    @click.outside="open = false; selecting = null; hoverDate = null;"
    class="relative"
>
    {{-- Label --}}
    @if($label)
        <label class="block text-sm font-medium text-gray-700 mb-1">
            @if($icon)
                <x-dynamic-component :component="$icon" class="h-4 w-4 inline mr-1 text-gray-400" />
            @endif
            {{ $label }}
        </label>
    @endif

    {{-- Trigger Button --}}
    <button
        @click="open = !open"
        type="button"
        class="w-full bg-white border border-gray-300 rounded-md shadow-sm px-3 py-2 text-left text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors hover:border-gray-400"
    >
        <div class="flex items-center justify-between">
            <span class="flex items-center gap-1.5 truncate">
                <template x-if="activePreset">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800" x-text="presets[activePreset]?.label"></span>
                </template>
                <template x-if="!activePreset && dateFrom && dateTo">
                    <span class="text-gray-700" x-text="displayText"></span>
                </template>
                <template x-if="!activePreset && (!dateFrom || !dateTo)">
                    <span class="text-gray-500">{{ __('Select date range...') }}</span>
                </template>
            </span>
            <svg class="h-4 w-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </button>

    {{-- Dropdown Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 mt-1 {{ $align === 'left' ? 'ltr:left-0 rtl:right-0' : 'ltr:right-0 rtl:left-0' }} w-[calc(100vw-2rem)] sm:w-auto sm:min-w-[640px] bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        x-cloak
    >
        <div class="flex flex-col sm:flex-row">
            {{-- Presets Sidebar --}}
            <div class="w-full sm:w-40 border-b sm:border-b-0 sm:border-r border-gray-200 py-2 flex flex-row sm:flex-col overflow-x-auto sm:overflow-x-visible">
                <template x-for="(preset, key) in presets" :key="key">
                    <button
                        @click="applyPreset(key)"
                        type="button"
                        class="w-full text-left px-3 py-2 text-sm transition-colors"
                        :class="activePreset === key ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-700 hover:bg-gray-50'"
                        x-text="preset.label"
                    ></button>
                </template>
                <div class="mt-0 sm:mt-auto border-t-0 sm:border-t border-gray-200 pt-0 sm:pt-2 px-3">
                    <button
                        @click="clearDates()"
                        type="button"
                        class="w-full text-left text-sm text-gray-500 hover:text-gray-700 py-1"
                    >{{ __('Reset') }}</button>
                </div>
            </div>

            {{-- Dual Calendar --}}
            <div class="flex-1 p-3">
                {{-- Navigation --}}
                <div class="flex items-center justify-between mb-3">
                    <button @click="prevMonth()" type="button" class="p-1 hover:bg-gray-100 rounded transition-colors">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <div class="flex gap-8 text-sm font-semibold text-gray-800">
                        <span x-text="monthName(leftMonth) + ' ' + leftYear"></span>
                        <span x-text="monthName(rightMonth) + ' ' + rightYear"></span>
                    </div>
                    <button @click="nextMonth()" type="button" class="p-1 hover:bg-gray-100 rounded transition-colors">
                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    {{-- Left Month --}}
                    <div class="flex-1">
                        <div class="grid grid-cols-7 mb-1">
                            <template x-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']">
                                <div class="text-center text-xs font-medium text-gray-500 py-1" x-text="d"></div>
                            </template>
                        </div>
                        <div class="grid grid-cols-7">
                            <template x-for="(dayObj, idx) in calendarDays(leftYear, leftMonth)" :key="'l'+idx">
                                <div
                                    @click="dayObj.current && selectDate(dayObj.date)"
                                    @mouseenter="onHover(dayObj.date)"
                                    class="text-center text-sm py-1.5 transition-colors"
                                    :class="dayClasses(dayObj)"
                                    x-text="dayObj.day"
                                ></div>
                            </template>
                        </div>
                    </div>

                    {{-- Right Month --}}
                    <div class="flex-1">
                        <div class="grid grid-cols-7 mb-1">
                            <template x-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']">
                                <div class="text-center text-xs font-medium text-gray-500 py-1" x-text="d"></div>
                            </template>
                        </div>
                        <div class="grid grid-cols-7">
                            <template x-for="(dayObj, idx) in calendarDays(rightYear, rightMonth)" :key="'r'+idx">
                                <div
                                    @click="dayObj.current && selectDate(dayObj.date)"
                                    @mouseenter="onHover(dayObj.date)"
                                    class="text-center text-sm py-1.5 transition-colors"
                                    :class="dayClasses(dayObj)"
                                    x-text="dayObj.day"
                                ></div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Selected range display --}}
                <div class="mt-3 pt-3 border-t border-gray-200 text-xs text-gray-500 text-center" x-text="displayText"></div>
            </div>
        </div>
    </div>
</div>
