<div x-data="{
    menuOpen: false,
    exporting: false,
    toast: { show: false, type: 'success', message: '' },
    showToast(type, message) {
        this.toast = { show: true, type, message };
        setTimeout(() => this.toast.show = false, 3000);
    },
    async handleExport(type) {
        if (this.exporting) return;
        this.exporting = type;
        this.menuOpen = false;

        try {
            if (type === 'print') {
                window.print();
            }
            // PDF/Excel/CSV handled via Livewire dispatch
            this.$wire.dispatch('export-requested', { type });
        } catch (e) {
            this.showToast('error', '{{ __("common.export_failed") }}');
        } finally {
            setTimeout(() => this.exporting = false, 1000);
        }
    }
}" @click.outside="menuOpen = false" class="relative inline-block text-start">
    <button
        @click="menuOpen = !menuOpen"
        :disabled="exporting"
        class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-2 border border-white/15 rounded-md shadow-sm text-sm font-medium text-gray-200 bg-gray-900 hover:bg-gray-950 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
    >
        <x-heroicon-o-arrow-down-tray class="h-4 w-4 me-2" />
        <span class="hidden sm:inline">{{ __('dashboard.export') }}</span>
        <span class="sm:hidden">{{ __('buttons.export_report') }}</span>
        <x-heroicon-o-chevron-down class="h-4 w-4 ms-1" />
    </button>

    <!-- Dropdown -->
    <div
        x-show="menuOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute end-0 z-10 mt-2 w-48 sm:w-56 origin-top-right rounded-md bg-gray-900 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        x-cloak
    >
        <div class="py-1">
            <button @click="handleExport('pdf')" :disabled="exporting" class="flex items-center w-full px-4 py-2 text-sm text-gray-200 hover:bg-white/5 disabled:opacity-50">
                <x-heroicon-o-document class="h-4 w-4 me-3 text-red-500" />
                {{ __('common.export_as_pdf') }}
            </button>
            <button @click="handleExport('excel')" :disabled="exporting" class="flex items-center w-full px-4 py-2 text-sm text-gray-200 hover:bg-white/5 disabled:opacity-50">
                <x-heroicon-o-table-cells class="h-4 w-4 me-3 text-green-500" />
                {{ __('common.export_as_excel') }}
            </button>
            <button @click="handleExport('csv')" :disabled="exporting" class="flex items-center w-full px-4 py-2 text-sm text-gray-200 hover:bg-white/5 disabled:opacity-50">
                <x-heroicon-o-document-text class="h-4 w-4 me-3 text-blue-500" />
                {{ __('common.export_as_csv') }}
            </button>
            <div class="border-t border-white/8 my-1"></div>
            <button @click="handleExport('print')" :disabled="exporting" class="flex items-center w-full px-4 py-2 text-sm text-gray-200 hover:bg-white/5 disabled:opacity-50">
                <x-heroicon-o-printer class="h-4 w-4 me-3 text-gray-500" />
                {{ __('buttons.print_report') }}
            </button>
        </div>
    </div>

    <!-- Toast -->
    <div
        x-show="toast.show"
        x-transition
        class="fixed bottom-4 end-4 z-50 flex items-center p-4 rounded-md shadow-lg"
        :class="toast.type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'"
        x-cloak
    >
        <span x-text="toast.message"></span>
    </div>
</div>
