        {{-- Monthly AI Impressions — client vs competitors --}}
        @php
            $competitorsWithHistory = collect($competitors)->filter(fn($c) => collect($c['visibility'] ?? [])->contains(fn($v) => isset($v['ai_impressions'])));
            $clientMonthLabels = collect($aiMonths ?? [])->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m)->format('M Y'))->values()->toArray();
        @endphp
        @if(count($aiMonths ?? []) > 1 && $competitorsWithHistory->count() > 0)
        <div class="card p-5 mt-4">
            <h3 class="text-sm font-semibold text-white mb-4">AI Impressions — Client vs Competitors</h3>
            <div x-data="{
                destroy() { Chart.getChart(this.$refs.cv)?.destroy(); },
                init() {
                    const labels = @js($clientMonthLabels);
                    const clientData = @js(collect($aiMonths ?? [])->pluck('ai_impressions')->map(fn($v) => (int)($v ?? 0))->values()->toArray());
                    const brandColor = getComputedStyle(document.documentElement).getPropertyValue('--brand-500')?.trim() || '#ef4444';
                    const compColors = ['#D4D4D2','#A8A8A4','#787774','#5C5C58','#EF4444','#3F3F3C'];
                    const datasets = [{
                        label: @js($dashboardData['tenant']['name'] ?? 'Client'),
                        data: clientData, borderColor: brandColor, backgroundColor: brandColor + '20',
                        borderWidth: 2.5, fill: false, tension: 0.3, pointRadius: 4, pointBackgroundColor: brandColor,
                    }];
                    @foreach($competitorsWithHistory as $ci => $comp)
                    @php $compMonthMap = collect($comp['visibility'])->keyBy('report_month'); @endphp
                    datasets.push({
                        label: @js($comp['name']),
                        data: @js(collect($clientMonthLabels)->map(function($label) use ($compMonthMap) {
                            $month = \Carbon\Carbon::createFromFormat('M Y', $label)->format('Y-m');
                            return $compMonthMap[$month]['ai_impressions'] ?? null;
                        })->toArray()),
                        borderColor: compColors[{{ $ci }} % compColors.length], borderWidth: 1.5,
                        fill: false, tension: 0.3, pointRadius: 3, borderDash: [4, 3],
                    });
                    @endforeach
                    Chart.getChart(this.$refs.cv)?.destroy();
                    new Chart(this.$refs.cv, { type: 'line', data: { labels, datasets },
                        options: { responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: true, labels: { color: '#9ca3af', font: { size: 11 }, boxWidth: 24 } } },
                            scales: {
                                x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } },
                                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#6b7280', font: { size: 11 } } },
                            }
                        }
                    });
                }
            }"><div style="height:240px;"><canvas x-ref="cv"></canvas></div></div>
        </div>
        @endif
