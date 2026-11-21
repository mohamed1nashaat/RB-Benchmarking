<div class="ai-score-list" aria-label="Semrush score comparison">
    @foreach($entitiesWithScore as $entity)
        <div class="ai-score-row {{ $entity['is_client'] ? 'is-client' : '' }}">
            <span class="ai-score-name">{{ $entity['name'] }} @if($entity['is_client'])<small>You</small>@endif</span>
            <div class="ai-score-track"><div style="width: {{ max(0, min(100, $entity['semrush_score'])) }}%"></div></div>
            <strong>{{ $entity['semrush_score'] }}</strong>
        </div>
    @endforeach
</div>
