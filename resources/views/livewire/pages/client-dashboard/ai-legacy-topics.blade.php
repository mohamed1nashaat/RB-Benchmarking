    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- ── Semrush AI Topics ─────────────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if(!empty($aiSnapshot['semrush_top_topics']))
    <div class="card p-5">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-sm font-semibold text-white">Semrush AI Topics</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    @if($aiSnapshot['semrush_topics_count'] ?? null)
                        {{ $aiSnapshot['semrush_topics_count'] }} topics tracked ·
                        {{ $aiSnapshot['semrush_prompts_count'] ?? '?' }} AI prompts ·
                        showing top {{ count($aiSnapshot['semrush_top_topics']) }}
                    @endif
                </p>
            </div>
            @if($semrushScore !== null)
            <div class="flex-shrink-0 text-right">
                <p class="text-[11px] text-gray-500">AI Score</p>
                <p class="text-xl font-bold tabular-nums {{ $semrushScore >= 50 ? 'text-emerald-400' : ($semrushScore >= 20 ? 'text-yellow-400' : 'text-gray-400') }}">
                    {{ $semrushScore }}<span class="text-sm text-gray-600 font-normal"> / 100</span>
                </p>
            </div>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-white/10 text-left">
                        <th class="pb-2.5 text-gray-500 font-medium pr-4">Topic</th>
                        <th class="pb-2.5 text-gray-500 font-medium pr-4">Market</th>
                        <th class="pb-2.5 text-gray-500 font-medium text-right pr-4">AI Visibility</th>
                        <th class="pb-2.5 text-gray-500 font-medium text-right pr-4">Difficulty</th>
                        <th class="pb-2.5 text-gray-500 font-medium text-right pr-4">Mentions</th>
                        <th class="pb-2.5 text-gray-500 font-medium text-right">Search Vol.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach(collect($aiSnapshot['semrush_top_topics']) as $topic)
                        @php
                            $vis  = $topic['visibility'] ?? 0;
                            $diff = $topic['difficulty'] ?? 0;
                            $visColor = $vis >= 50 ? '#22c55e' : ($vis >= 20 ? '#eab308' : '#6b7280');
                            $diffColor = $diff <= 30 ? '#22c55e' : ($diff <= 60 ? '#eab308' : '#ef4444');
                        @endphp
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="py-2.5 text-gray-200 pr-4">{{ $topic['topic'] }}</td>
                            <td class="py-2.5 text-gray-500 pr-4">{{ $topic['market'] ?? '—' }}</td>
                            <td class="py-2.5 text-right pr-4">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-16 h-1.5 rounded-full bg-white/10 overflow-hidden">
                                        <div class="h-full rounded-full" style="width:{{ $vis }}%;background:{{ $visColor }};"></div>
                                    </div>
                                    <span class="tabular-nums font-medium w-6 text-right" style="color:{{ $visColor }}">{{ $vis }}</span>
                                </div>
                            </td>
                            <td class="py-2.5 text-right pr-4">
                                <span class="tabular-nums" style="color:{{ $diffColor }}">{{ $diff }}</span>
                            </td>
                            <td class="py-2.5 text-gray-400 tabular-nums text-right pr-4">{{ $topic['mentions'] ?? '—' }}</td>
                            <td class="py-2.5 text-gray-500 tabular-nums text-right">{{ number_format($topic['volume'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
