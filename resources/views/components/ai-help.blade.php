@props(['h'])
@php [$helpTitle, $helpSource, $helpWhy] = $h; @endphp
<span class="air-help" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
    <button type="button" class="air-help-btn" @click="open = !open" aria-expanded="false" :aria-expanded="open.toString()" aria-label="Where this comes from: {{ $helpTitle }}">?</button>
    <span class="air-help-pop" x-show="open" x-cloak x-transition.opacity role="tooltip">
        <b>{{ $helpTitle }}</b>
        <span class="hp"><span>Where it comes from</span>{{ $helpSource }}</span>
        <span class="hp"><span>Why it matters</span>{{ $helpWhy }}</span>
    </span>
</span>
