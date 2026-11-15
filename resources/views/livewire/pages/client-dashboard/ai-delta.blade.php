@if(!empty($delta['text']))
    <div class="air-delta {{ $delta['up'] ? 'up' : 'down' }}">@if($delta['up'])<x-heroicon-s-arrow-trending-up />@else<x-heroicon-s-arrow-trending-down />@endif{{ ltrim($delta['text'], '+−') }}<span class="vs">{{ $delta['vs'] }}</span></div>
@else
    <div class="air-delta"><span class="vs" style="margin:0">{{ $delta['vs'] ?? 'No earlier capture to compare' }}</span></div>
@endif
