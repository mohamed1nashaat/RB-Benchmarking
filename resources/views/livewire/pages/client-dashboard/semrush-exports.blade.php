@php
    $semrush = $this->semrushData;
@endphp
@if($semrush)
<div class="card p-5 space-y-5 semrush-explorer">
    <div>
        <h3 class="text-sm font-semibold text-white">Topic &amp; prompt research</h3>
        <p class="text-xs text-gray-400 mt-1">Report captured {{ $semrush['report']->captured_at }} · {{ $semrush['report']->domain }}</p>
        <p class="text-xs text-gray-500 mt-2">Explore imported topics and AI responses for one brand. Filters apply to the records in this section.</p>
    </div>
    <div class="semrush-filter-bar">
        <label class="semrush-filter"><span>Report month</span><select aria-label="Semrush report month" wire:model.live="semrushMonth" class="input-field">
            <option value="">Latest report</option>
            @foreach($semrush['months'] as $month)<option value="{{ $month }}">{{ $month }}</option>@endforeach
        </select></label>
        <label class="semrush-filter"><span>Brand</span><select aria-label="Semrush brand" wire:model.live="semrushDomain" class="input-field">
            <option value="">Client brand</option>
            @foreach($semrush['reports'] as $report)<option value="{{ $report->domain }}">{{ $report->domain }}</option>@endforeach
        </select></label>
        <label class="semrush-filter"><span>Country</span><select aria-label="Semrush country" wire:model.live="semrushCountry" class="input-field">
            <option value="">All countries</option>
            @foreach($semrush['countries'] as $country)<option value="{{ $country }}">{{ strtoupper($country) }}</option>@endforeach
        </select></label>
        <label class="semrush-filter"><span>AI platform</span><select aria-label="Semrush AI platform" wire:model.live="semrushPlatform" class="input-field">
            <option value="">All AI platforms</option>
            @foreach(['chatgpt' => 'ChatGPT', 'gemini' => 'Gemini', 'google_ai_mode' => 'Google AI Mode', 'google_ai_overview' => 'AI Overviews'] as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select></label>
        <label class="semrush-filter semrush-search"><span>Search</span><input aria-label="Search Semrush exports" wire:model.live.debounce.300ms="semrushSearch" placeholder="Search topics and responses…" class="input-field" /></label>
        <button type="button" wire:click="resetSemrushFilters" class="semrush-reset">Reset filters</button>
    </div>
    @php
        $platformNames = ['chatgpt' => 'ChatGPT', 'gemini' => 'Gemini', 'google_ai_mode' => 'Google AI Mode', 'google_ai_overview' => 'AI Overviews'];
        $platformColors = ['chatgpt' => '#D4D4D2', 'gemini' => '#EF4444', 'google_ai_mode' => '#787774', 'google_ai_overview' => '#A8A8A4'];
        $promptCount = $semrush['prompts']->count();
        $mentionCount = $semrush['prompts']->filter(fn ($p) => str_contains($p['categories'], 'mentions_target'))->count();
        $citationCount = $semrush['prompts']->filter(fn ($p) => str_contains($p['categories'], 'cites_target'))->count();
        $bothCount = $semrush['prompts']->filter(fn ($p) => str_contains($p['categories'], 'mentions_target') && str_contains($p['categories'], 'cites_target'))->count();
        $presence = [
            ['label' => 'Mention only', 'count' => $mentionCount - $bothCount, 'color' => '#D4D4D2'],
            ['label' => 'Citation only', 'count' => $citationCount - $bothCount, 'color' => '#787774'],
            ['label' => 'Both', 'count' => $bothCount, 'color' => '#EF4444'],
            ['label' => 'Neither', 'count' => $promptCount - $mentionCount - $citationCount + $bothCount, 'color' => '#3F3F3C'],
        ];
        $countryCounts = $semrush['prompts']->countBy('country')->sortDesc()->take(6);
        $brandMax = max(1, $semrush['summary']->max('prompts'));
        $summaryOnly = empty($semrush['report']->topics) && empty($semrush['report']->prompts);
        $unfiltered = !$semrushCountry && !$semrushPlatform && !$semrushSearch;
        $metrics = $semrush['report']->metrics ?? [];
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4" data-testid="semrush-metrics">
        @foreach([
            ['label' => 'Topic rows', 'value' => $summaryOnly && $unfiltered ? ($metrics['topics'] ?? 0) : $semrush['topics']->count(), 'color' => '#A8A8A4'],
            ['label' => 'Prompt records', 'value' => $summaryOnly && $unfiltered ? ($metrics['prompts'] ?? 0) : $promptCount, 'color' => '#EF4444'],
            ['label' => 'Brand mentions', 'value' => $summaryOnly && $unfiltered ? ($metrics['mentions'] ?? 0) : $mentionCount, 'color' => '#D4D4D2'],
            ['label' => 'Website citations', 'value' => $summaryOnly && $unfiltered ? ($metrics['citations'] ?? 0) : $citationCount, 'color' => '#787774'],
        ] as $metric)
        <div class="rounded-xl border border-white/10 p-4" >
            <p class="text-xs text-gray-400">{{ $metric['label'] }}</p>
            <p class="text-3xl font-semibold mt-2 tabular-nums text-white">{{ number_format($metric['value']) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $summaryOnly && $unfiltered ? 'Supplied brand summary' : 'Current selection' }}</p>
        </div>
        @endforeach
    </div>
    <details class="ai-disclosure" wire:ignore.self><summary>Coverage breakdown <span>Platforms, markets and brand presence</span></summary>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <section class="rounded-xl border border-white/10 p-4" aria-label="AI platform coverage chart">
            <h4 class="text-sm font-semibold text-white">AI platform coverage</h4>
            <p class="text-xs text-gray-500 mt-1 mb-5">{{ $semrush['report']->domain }} · matching prompt records</p>
            @foreach($platformNames as $key => $name)
                @php $count = $semrush['prompts']->where('llm', $key)->count(); @endphp
                <div class="mb-5">
                    <div class="flex justify-between text-xs mb-2"><span class="text-gray-300">{{ $name }}</span><span style="color: {{ $platformColors[$key] }}">{{ $count }} <span class="text-gray-500">· {{ $promptCount ? round($count / $promptCount * 100) : 0 }}%</span></span></div>
                    <div class="h-3 rounded-full bg-white/5 overflow-hidden"><div class="h-full rounded-full" style="width: {{ $promptCount ? $count / $promptCount * 100 : 0 }}%; background: {{ $platformColors[$key] }}"></div></div>
                </div>
            @endforeach
            @if(!$promptCount)<p class="text-xs text-gray-500">No prompt records match these filters.</p>@endif
        </section>
        <section class="rounded-xl border border-white/10 p-4" aria-label="Mentions and citations chart">
            <h4 class="text-sm font-semibold text-white">How the brand appears</h4>
            <p class="text-xs text-gray-500 mt-1 mb-5">Matching responses split into exclusive groups</p>
            <div class="flex h-8 overflow-hidden rounded-lg bg-white/5" role="img" aria-label="{{ $mentionCount - $bothCount }} mention only, {{ $citationCount - $bothCount }} citation only, {{ $bothCount }} both, {{ $promptCount - $mentionCount - $citationCount + $bothCount }} neither">
                @foreach($presence as $segment)
                    <div title="{{ $segment['label'] }}: {{ $segment['count'] }}" style="width: {{ $promptCount ? $segment['count'] / $promptCount * 100 : 0 }}%; background: {{ $segment['color'] }}"></div>
                @endforeach
            </div>
            <div class="grid grid-cols-2 gap-4 mt-5">
                @foreach($presence as $segment)
                <div class="text-xs"><span style="color:{{ $segment['color'] }}">●</span> <span class="text-gray-400">{{ $segment['label'] }}</span><p class="text-lg font-semibold text-white mt-1">{{ $segment['count'] }} <span class="text-xs font-normal text-gray-500">{{ $promptCount ? round($segment['count'] / $promptCount * 100) : 0 }}%</span></p></div>
                @endforeach
            </div>
        </section>
        <section class="rounded-xl border border-white/10 p-4" aria-label="Country coverage chart">
            <h4 class="text-sm font-semibold text-white">Top markets</h4>
            <p class="text-xs text-gray-500 mt-1 mb-5">Top 6 countries by matching prompt records</p>
            @forelse($countryCounts as $country => $count)
            <div class="flex items-center gap-3 mb-3">
                <span class="text-xs text-gray-400 w-8">{{ strtoupper($country) }}</span>
                <div class="flex-1 h-3 rounded bg-white/5 overflow-hidden"><div class="h-full rounded" style="width: {{ $count / max(1, $countryCounts->max()) * 100 }}%; background: #EF4444"></div></div>
                <span class="text-xs text-gray-300 w-8 text-right">{{ $count }}</span>
            </div>
            @empty<p class="text-xs text-gray-500">No matching markets.</p>@endforelse
        </section>
    </div>
    </details>
    @if(empty($semrush['report']->topics) && empty($semrush['report']->prompts))
        <p class="text-xs text-gray-400">Summary totals supplied manually. Detailed topic and prompt exports have not been imported for this brand.</p>
    @endif
    <details class="ai-disclosure" wire:ignore.self>
        <summary class="text-sm text-white cursor-pointer">Topics · {{ $semrush['topics']->count() }} matching rows</summary>
        <p class="text-xs text-gray-500 my-2">Sorted by visibility. Platform filter applies to prompt records only. Volume trend contains the 12 values supplied by Semrush; the export does not date those values.</p>
        <div class="overflow-x-auto"><table class="w-full text-xs text-gray-300">
            <thead><tr class="text-left border-b border-white/10"><th class="p-2">Topic / market</th><th>Visibility</th><th>Difficulty</th><th>Mentions</th><th>Volume</th><th>Details</th></tr></thead>
            <tbody>@forelse($semrush['topics']->take($semrushLimit) as $topic)
                <tr class="border-b border-white/5"><td class="p-2"><span dir="auto">{{ $topic['name'] }}</span><span class="block text-gray-500">{{ strtoupper($topic['country']) }}</span></td><td>{{ $topic['visibility'] }}%</td><td>{{ $topic['difficulty'] }}</td><td>{{ $topic['mentions'] }}</td><td>{{ number_format($topic['volume']) }}</td><td><details><summary class="cursor-pointer">Trend &amp; intent</summary><p>{{ $topic['volume_trend'] }}</p><p>{{ $topic['intents'] }}</p></details></td></tr>
            @empty<tr><td colspan="6" class="p-3">No matching topics.</td></tr>@endforelse</tbody>
        </table></div>
    </details>
    @php
        $brandName = $semrush['report']->is_client ? ($dashboardData['client']['name'] ?? null) : null;
        // Brand spellings to highlight: "Al Shifa", "AlShifa", "Al-Shifa" and the distinctive last word ("Shifa").
        $brandWords = $brandName ? preg_split('/\s+/u', trim($brandName)) : [];
        $markTerms = collect([$semrushSearch, $brandName, implode('', $brandWords), implode('-', $brandWords), count($brandWords) > 1 ? end($brandWords) : null, \Illuminate\Support\Str::before($semrush['report']->domain ?? '', '.')])
            ->filter(fn ($t) => is_string($t) && mb_strlen(trim($t)) >= 3)->map(fn ($t) => trim($t))->unique()->values();
        $markText = function (?string $text) use ($markTerms) {
            $html = e($text ?? '');
            if ($markTerms->isEmpty()) {
                return $html;
            }
            // One pass, longest spelling first, so overlapping terms never nest.
            $pattern = '/'.$markTerms->sortByDesc(fn ($t) => mb_strlen($t))->map(fn ($t) => preg_quote(e($t), '/'))->implode('|').'/iu';
            return preg_replace($pattern, '<mark style="background:rgba(239,68,68,.18);color:inherit;border-radius:3px;padding:0 2px">$0</mark>', $html) ?? $html;
        };
        $strengthMeta = [
            'strong' => ['label' => 'Named and cited', 'short' => 'Strong', 'row' => 'background:rgba(22,163,74,.07);box-shadow:inset 3px 0 0 var(--air-good)', 'text' => 'color:var(--air-good)'],
            'mentioned' => ['label' => 'Named, not cited', 'short' => 'Mentioned only', 'row' => 'box-shadow:inset 3px 0 0 var(--air-neutral)', 'text' => 'color:var(--ai-text)'],
            'cited' => ['label' => 'Cited, not named', 'short' => 'Cited only', 'row' => 'background:rgba(239,68,68,.06);box-shadow:inset 3px 0 0 var(--air-bad)', 'text' => 'color:var(--air-bad)'],
            'none' => ['label' => 'Not present', 'short' => 'Not present', 'row' => '', 'text' => 'color:var(--ai-muted)'],
        ];
        $sortOptions = [
            'strongest' => 'Strongest first',
            'weakest' => 'Weakest first',
            'volume_desc' => 'Search volume: high to low',
            'volume_asc' => 'Search volume: low to high',
            'visibility_desc' => 'Topic visibility: high to low',
            'visibility_asc' => 'Topic visibility: low to high',
            'brands_desc' => 'Most competing brands',
            'brands_asc' => 'Fewest competing brands',
        ];
        $promptRows = $semrush['promptRows'];
        $sc = $semrush['strengthCounts'];
    @endphp
    <details class="ai-disclosure" wire:ignore.self open>
        <summary class="text-sm text-white cursor-pointer">AI prompt responses · {{ $promptRows->count() }} {{ $semrushPresence ? 'of '.$semrush['prompts']->count().' ' : '' }}matching records</summary>
        <div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:12px 20px;margin-top:12px">
            <label class="semrush-filter" style="min-width:240px"><span>Sort prompts</span><select aria-label="Sort prompts" wire:model.live="semrushPromptSort" class="input-field">
                @foreach($sortOptions as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select></label>
            <div class="ai-segmented" role="group" aria-label="Show prompts by brand presence">
                @foreach(['' => ['All', $semrush['prompts']->count()], 'strong' => ['Strong', $sc['strong'] ?? 0], 'mentioned' => ['Mentioned only', $sc['mentioned'] ?? 0], 'cited' => ['Cited only', $sc['cited'] ?? 0]] as $key => [$label, $n])
                    <button type="button" wire:click="setSemrushPresence('{{ $key }}')" class="{{ $semrushPresence === $key ? 'is-active' : '' }}" aria-pressed="{{ $semrushPresence === $key ? 'true' : 'false' }}">
                        @if($key)<span aria-hidden="true" style="display:inline-block;width:8px;height:8px;border-radius:2px;margin-inline-end:6px;background:{{ ['strong' => 'var(--air-good)', 'mentioned' => 'var(--air-neutral)', 'cited' => 'var(--air-bad)'][$key] }}"></span>@endif{{ $label }} <span class="tabular-nums" style="color:var(--ai-muted)">{{ $n }}</span>
                    </button>
                @endforeach
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-3"><b style="color:var(--air-good)">Strong</b>: the answer names the brand and links the website. <b style="color:var(--ai-text)">Mentioned only</b>: named, no link. <b style="color:var(--air-bad)">Cited only</b>: the website is used as a source but the brand is never named, the weakest result. Brand names and your search are highlighted in the text.</p>
        <div class="overflow-x-auto"><table class="w-full text-xs text-gray-300 mt-3">
            <thead><tr class="text-left border-b border-white/10"><th class="p-2">Prompt / topic</th><th class="p-2">Result</th><th class="p-2">Market / platform</th><th class="p-2 text-right">Search volume</th><th class="p-2 text-right">Topic visibility</th><th class="p-2 text-right">Brands named</th><th class="p-2">Response</th></tr></thead>
            <tbody>@forelse($promptRows->take($semrushLimit) as $prompt)
                @php $sm = $strengthMeta[$prompt['strength']] ?? $strengthMeta['none']; @endphp
                <tr class="border-b border-white/5 align-top" style="{{ $sm['row'] }}" data-strength="{{ $prompt['strength'] }}">
                    <td class="p-2"><p dir="auto">{!! $markText($prompt['prompt']) !!}</p><p dir="auto" class="text-gray-500 mt-1">{{ $prompt['topic_name'] }}</p></td>
                    <td class="p-2 whitespace-nowrap"><b style="{{ $sm['text'] }};font-weight:600">{{ $sm['short'] }}</b><span class="block text-gray-500">{{ $sm['label'] }}</span></td>
                    <td class="p-2">{{ strtoupper($prompt['country']) }}<br>{{ ['chatgpt' => 'ChatGPT', 'gemini' => 'Gemini', 'google_ai_mode' => 'Google AI Mode', 'google_ai_overview' => 'AI Overviews'][$prompt['llm']] ?? $prompt['llm'] }}</td>
                    <td class="p-2 text-right tabular-nums">{{ number_format((int) $prompt['topic_volume']) }}</td>
                    <td class="p-2 text-right tabular-nums">{{ $prompt['topic_visibility'] }}%</td>
                    <td class="p-2 text-right tabular-nums">{{ $prompt['mentioned_brands_count'] }}</td>
                    <td class="p-2" style="min-width:220px"><details><summary class="cursor-pointer">View exported excerpt</summary><p dir="auto" class="mt-2">{!! $markText($prompt['brief_response']) !!}</p><p class="mt-2 text-gray-500">All sources: {{ $prompt['sources_count'] }} · Difficulty: {{ $prompt['topic_difficulty'] }}</p><p class="text-gray-500">Intent: {{ $prompt['topic_intents'] }}</p></details></td>
                </tr>
            @empty<tr><td colspan="7" class="p-3">No matching prompt records.</td></tr>@endforelse</tbody>
        </table></div>
    </details>
    @if(max($semrush['topics']->count(), $semrush['promptRows']->count()) > $semrushLimit)
        <button wire:click="showMoreSemrush" class="text-xs text-brand-400">Show more rows (up to {{ $semrushLimit }} currently shown per table)</button>
    @endif
    <p class="text-xs text-gray-500">Sources: {{ implode(' · ', $semrush['report']->source_files) }}{{ $semrush['report']->metrics ? ' · User-provided brand summary (score, cited pages, cited sources and totals)' : '' }}</p>
</div>
@endif
