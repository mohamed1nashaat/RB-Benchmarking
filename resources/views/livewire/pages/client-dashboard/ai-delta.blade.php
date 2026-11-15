@if(!empty($delta['text']))
    <div class="air-delta {{ $delta['up'] ? 'up' : 'down' }}"><span aria-hidden="true">{{ $delta['up'] ? '▲' : '▼' }}</span>{{ $delta['text'] }}<span class="vs">{{ $delta['vs'] }}</span></div>
@else
    <div class="air-delta"><span class="vs">{{ $delta['vs'] ?? 'No earlier capture to compare' }}</span></div>
@endif
