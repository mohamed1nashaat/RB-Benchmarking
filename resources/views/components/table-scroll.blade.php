@props(['maxHeight' => '600px'])

<div class="relative"
     x-data="{
         atStart: true,
         atEnd: true,
         check() {
             let el = this.$refs.scroll;
             if (!el) return;
             this.atStart = el.scrollLeft <= 1;
             this.atEnd  = el.scrollLeft >= el.scrollWidth - el.clientWidth - 1;
         }
     }"
     x-init="$nextTick(() => check())"
     x-on:resize.window.debounce.150ms="check()">

    {{-- Left scroll shadow --}}
    <div x-show="!atStart"
         x-transition:enter="transition-opacity duration-200"
         x-transition:leave="transition-opacity duration-200"
         class="absolute left-0 top-0 bottom-2 w-8 bg-gradient-to-r from-black/[0.06] to-transparent z-30 pointer-events-none"></div>

    {{-- Right scroll shadow --}}
    <div x-show="!atEnd"
         x-transition:enter="transition-opacity duration-200"
         x-transition:leave="transition-opacity duration-200"
         class="absolute right-0 top-0 bottom-2 w-8 bg-gradient-to-l from-black/[0.06] to-transparent z-30 pointer-events-none"></div>

    <div x-ref="scroll"
         x-on:scroll.passive="check()"
         {{ $attributes->merge([
             'class' => 'overflow-x-auto overflow-y-auto table-scroll-container',
             'style' => "max-height: {$maxHeight}",
         ]) }}>
        {{ $slot }}
    </div>
</div>
