<div class="ai-section-heading">
    <div><h2>Competitor comparison</h2><p>Compare one set of metrics at a time. All {{ $allEntities->count() }} brands remain visible.</p></div>
    <button type="button" wire:click="openCompetitorForm()" class="btn-secondary">Add competitor</button>
</div>
@include('livewire.pages.client-dashboard.ai-competitor-forms')
@php
    $metricGroups = [
        'visibility' => ['label' => 'Visibility', 'columns' => ['semrush_score' => 'AI score / 100', 'topics' => 'Topics', 'prompts' => 'Prompts', 'mentions' => 'Brand mentions', 'citations' => 'Website citations']],
        'sources' => ['label' => 'Cited sources', 'columns' => ['citations' => 'Website citations', 'cited_pages' => 'Cited pages', 'cited_sources' => 'Cited sources']],
        'audience' => ['label' => 'Audience estimates', 'columns' => ['monthly_audience' => 'Monthly audience', 'estimated_gemini_visits' => 'Estimated Gemini visits']],
        'analytics' => ['label' => 'Measured analytics', 'columns' => ['ai_impressions' => 'AI impressions', 'ai_sessions' => 'AI sessions', 'clarity' => 'Clarity / Copilot citations']],
    ];
@endphp
<div class="card ai-comparison" x-data="{ metricGroup: 'visibility' }">
    <div class="ai-comparison-toolbar">
        <div class="ai-segmented" role="group" aria-label="Competitor metric group">
            @foreach($metricGroups as $key => $group)
                <button type="button" @click="metricGroup = '{{ $key }}'" :class="metricGroup === '{{ $key }}' ? 'is-active' : ''" :aria-pressed="metricGroup === '{{ $key }}'">{{ $group['label'] }}</button>
            @endforeach
        </div>
        <p x-show="metricGroup !== 'analytics'">Semrush · {{ $snapshotLabel }} · Worldwide</p>
        <p x-show="metricGroup === 'analytics'" x-cloak>Traffic totals: all recorded months per brand. Clarity: latest recorded snapshot.</p>
    </div>
    @foreach($metricGroups as $key => $group)
    <div x-show="metricGroup === '{{ $key }}'" @if($key !== 'visibility') x-cloak @endif class="ai-table-scroll" tabindex="0" role="region" aria-label="{{ $group['label'] }} comparison">
        <table class="ai-comparison-table" aria-label="Competitor {{ strtolower($group['label']) }} comparison">
            <thead><tr><th scope="col">Brand</th>@foreach($group['columns'] as $label)<th scope="col">{{ $label }}</th>@endforeach<th scope="col" aria-label="Manage brand"></th></tr></thead>
            <tbody>
                @foreach($allEntities as $entity)
                <tr class="{{ $entity['is_client'] ? 'is-client' : '' }}">
                    <th scope="row"><span>{{ $entity['name'] }}</span>@if($entity['is_client'])<small class="ai-you-label">You</small>@endif<small class="ai-domain">{{ $entity['domain'] }}</small></th>
                    @foreach($group['columns'] as $metric => $label)
                        <td data-metric="{{ $metric }}">{{ isset($entity[$metric]) ? ($metric === 'monthly_audience' ? number_format($entity[$metric] / 1000, 1).'K' : number_format($entity[$metric])) : '—' }}</td>
                    @endforeach
                    <td>
                        @if($entity['comp_id'])
                        <details class="ai-row-actions"><summary aria-label="Manage {{ $entity['name'] }}">•••</summary><div>
                            <button type="button" wire:click="openCompetitorVisForm({{ $entity['comp_id'] }}, '{{ $snapshotLabel !== 'No snapshot' ? $snapshotLabel : now()->format('Y-m') }}')">Log metrics</button>
                            <button type="button" wire:click="openCompetitorForm({{ $entity['comp_id'] }})">Edit brand</button>
                            <button type="button" wire:click="deleteCompetitor({{ $entity['comp_id'] }})" wire:confirm="Remove {{ $entity['name'] }}?">Remove brand</button>
                        </div></details>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
    <p class="ai-table-note"><span x-show="metricGroup === 'visibility'">Mentions and citations can overlap; each brand has its own exported prompt set.</span><span x-show="metricGroup === 'audience'" x-cloak>Audience and Gemini visits are supplied estimates, separate from measured website traffic.</span> A dash means no supplied value; 0 is a reported zero.</p>
</div>

@if(collect($competitors ?? [])->contains(fn($c) => collect($c['visibility'] ?? [])->contains(fn($v) => isset($v['ai_impressions']))))
<details class="ai-disclosure"><summary>Impression history <span>Monthly comparison with available competitor data</span></summary>
@include('livewire.pages.client-dashboard.ai-competitor-history')
</details>
@endif
