<div class="max-w-3xl mx-auto space-y-6"
     @if($report && $report->isGenerating()) wire:poll.3s="pollStatus" @endif>

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <a href="{{ route('reports') }}" class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-300 transition-colors mb-2">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                All Reports
            </a>
            <h1 class="text-xl font-semibold tracking-tight text-white">
                {{ $report ? $report->name : __('reports.new_report') }}
            </h1>
        </div>
        {{-- Step indicator --}}
        <div class="flex items-center gap-1.5 text-xs text-gray-500 font-mono">
            @foreach([1 => 'Configure', 2 => 'Preview', 3 => 'Report'] as $n => $label)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded {{ $step === $n ? 'bg-brand-500/15 text-brand-400 border border-brand-500/30 font-semibold' : ($step > $n ? 'text-green-400' : '') }}">
                    @if($step > $n)
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    @else
                        {{ $n }}
                    @endif
                    {{ $label }}
                </span>
                @if($n < 3) <span>·</span> @endif
            @endforeach
        </div>
    </div>

    {{-- ═══ STEP 1: Configure ═══ --}}
    @if($step === 1)
    <div class="bg-[#161b22] border border-white/10 rounded-lg divide-y divide-white/8">
        <div class="px-6 py-5">
            <h2 class="text-sm font-semibold text-white mb-4">Report details</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-[13px] font-medium text-gray-300 mb-1.5">Report name</label>
                    <input wire:model="name" type="text"
                           class="w-full px-3 py-2 text-sm bg-white/5 border border-white/10 rounded-md text-white placeholder-gray-600 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition"
                           placeholder="e.g. Client X — Q4 Performance Review">
                    @error('name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-gray-300 mb-1.5">Period start</label>
                        <input wire:model="periodStart" type="date"
                               class="w-full px-3 py-2 text-sm bg-white/5 border border-white/10 rounded-md text-white focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition">
                        @error('periodStart') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-300 mb-1.5">Period end</label>
                        <input wire:model="periodEnd" type="date"
                               class="w-full px-3 py-2 text-sm bg-white/5 border border-white/10 rounded-md text-white focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition">
                        @error('periodEnd') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-[13px] font-medium text-gray-300 mb-1.5">Ad account <span class="text-gray-500 font-normal">(optional — leave blank for all accounts)</span></label>
                    <select wire:model.live="adAccountId"
                            class="w-full px-3 py-2 text-sm bg-white/5 border border-white/10 rounded-md text-gray-300 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition">
                        <option value="">All active accounts</option>
                        @foreach($adAccounts as $account)
                            <option value="{{ $account['id'] }}">{{ $account['account_name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-gray-300 mb-1.5">Platform <span class="text-gray-500 font-normal">(optional)</span></label>
                        <select wire:model="platform"
                                class="w-full px-3 py-2 text-sm bg-white/5 border border-white/10 rounded-md text-gray-300 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition">
                            <option value="">All platforms</option>
                            <option value="facebook">Facebook / Meta</option>
                            <option value="google">Google Ads</option>
                            <option value="tiktok">TikTok</option>
                            <option value="linkedin">LinkedIn</option>
                            <option value="snapchat">Snapchat</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-gray-300 mb-1.5">Industry <span class="text-gray-500 font-normal">(for benchmarks)</span></label>
                        <select wire:model="industry"
                                class="w-full px-3 py-2 text-sm bg-white/5 border border-white/10 rounded-md text-gray-300 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition">
                            <option value="">Not specified</option>
                            <option value="automotive">Automotive</option>
                            <option value="e_commerce_retail">E-commerce & Retail</option>
                            <option value="education">Education</option>
                            <option value="fashion_luxury">Fashion & Luxury</option>
                            <option value="finance_insurance">Finance & Insurance</option>
                            <option value="fmcg_cpg">FMCG / CPG</option>
                            <option value="food_beverage">Food & Beverage</option>
                            <option value="healthcare">Healthcare</option>
                            <option value="hospitality_food_services">Hospitality & Food Services</option>
                            <option value="professional_services">Professional Services</option>
                            <option value="real_estate">Real Estate</option>
                            <option value="technology">Technology</option>
                            <option value="travel_destinations">Travel & Destinations</option>
                        </select>
                    </div>
                </div>

                {{-- Language toggle --}}
                <div>
                    <label class="block text-[13px] font-medium text-gray-300 mb-1.5">Report language</label>
                    <div class="flex rounded-md border border-white/10 overflow-hidden w-fit">
                        <button type="button" wire:click="$set('language','en')"
                                class="{{ $language === 'en' ? 'bg-brand-600 text-white' : 'bg-white/5 text-gray-300 hover:bg-white/8' }} px-5 py-2 text-sm font-medium transition-colors">
                            English
                        </button>
                        <button type="button" wire:click="$set('language','ar')"
                                class="{{ $language === 'ar' ? 'bg-brand-600 text-white' : 'bg-white/5 text-gray-300 hover:bg-white/8' }} px-5 py-2 text-sm font-medium transition-colors border-l border-white/10">
                            العربية
                        </button>
                    </div>
                </div>

                {{-- Delivery Schedule --}}
                <div class="border-t border-white/8 pt-4 space-y-3">
                    <label class="block text-[13px] font-semibold text-gray-300">Delivery Schedule</label>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach(['none' => 'One-time', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $val => $lbl)
                        <label class="relative flex items-center justify-center p-3 rounded-lg border-2 cursor-pointer transition-colors
                            {{ $scheduleFrequency === $val ? 'border-brand-500 bg-brand-500/15' : 'border-white/10 bg-white/5 hover:border-white/20' }}">
                            <input type="radio" wire:model.live="scheduleFrequency" value="{{ $val }}" class="sr-only">
                            <span class="text-[13px] font-medium {{ $scheduleFrequency === $val ? 'text-brand-400' : 'text-gray-400' }}">{{ $lbl }}</span>
                        </label>
                        @endforeach
                    </div>
                    @if($scheduleFrequency !== 'none')
                    <div>
                        <label class="block text-[13px] font-medium text-gray-300 mb-1.5">Delivery emails</label>
                        <input type="text" wire:model="deliveryEmails"
                               placeholder="client@example.com, manager@agency.com"
                               class="w-full px-3 py-2 text-sm bg-white/5 border border-white/10 rounded-md text-white placeholder-gray-600 focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/15 transition">
                        <p class="mt-1 text-xs text-gray-500">Separate multiple emails with commas. Creator is always notified.</p>
                    </div>
                    @endif
                </div>


            </div>
        </div>
        <div class="px-6 py-4 flex justify-end">
            <button wire:click="nextStep"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold rounded-md transition-colors">
                Preview data
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                </svg>
            </button>
        </div>
    </div>
    @endif

    {{-- ═══ STEP 2: Preview metrics ═══ --}}
    @if($step === 2)
    <div class="space-y-4">
        <div class="bg-[#161b22] border border-white/10 rounded-lg">
            <div class="px-6 py-4 border-b border-white/8">
                <h2 class="text-sm font-semibold text-white">Data preview</h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    Showing aggregated metrics for <span class="font-mono text-gray-300">{{ $periodStart }}</span> → <span class="font-mono text-gray-300">{{ $periodEnd }}</span>.
                    The AI will use this data to generate your report.
                </p>
            </div>

            @if($report && $report->performance_data)
                @php $pd = $report->performance_data; @endphp
                <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-y divide-white/8">
                    <div class="px-5 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">Total Spend</div>
                        <div class="text-xl font-bold font-mono">SAR {{ number_format($pd['spend'] ?? 0, 0) }}</div>
                    </div>
                    <div class="px-5 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">Impressions</div>
                        <div class="text-xl font-bold font-mono">{{ number_format($pd['impressions'] ?? 0) }}</div>
                    </div>
                    <div class="px-5 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">CTR</div>
                        <div class="text-xl font-bold font-mono">{{ $pd['ctr'] ?? 0 }}%</div>
                    </div>
                    <div class="px-5 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">CPL</div>
                        <div class="text-xl font-bold font-mono">{{ $pd['cpl'] > 0 ? 'SAR ' . number_format($pd['cpl'], 0) : 'N/A' }}</div>
                    </div>
                    <div class="px-5 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">Clicks</div>
                        <div class="text-xl font-bold font-mono">{{ number_format($pd['clicks'] ?? 0) }}</div>
                    </div>
                    <div class="px-5 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">CPC</div>
                        <div class="text-xl font-bold font-mono">SAR {{ $pd['cpc'] ?? 0 }}</div>
                    </div>
                    <div class="px-5 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">CPM</div>
                        <div class="text-xl font-bold font-mono">SAR {{ $pd['cpm'] ?? 0 }}</div>
                    </div>
                    <div class="px-5 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1">Campaigns</div>
                        <div class="text-xl font-bold font-mono">{{ $pd['campaigns'] ?? 0 }}</div>
                    </div>
                </div>
                @if(!empty($pd['benchmarks']))
                <div class="border-t border-white/8 px-5 py-4">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-3">MENA Benchmarks — {{ ucfirst($platform) }} · {{ str_replace('_', ' ', ucfirst($industry)) }}</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($pd['benchmarks'] as $metric => $percentiles)
                            <div class="bg-white/5 border border-white/10 rounded px-3 py-2 text-xs">
                                <span class="font-semibold text-gray-300 uppercase tracking-wide">{{ $metric }}</span>
                                <span class="mx-1 text-white/20">|</span>
                                <span class="text-gray-400">P25: {{ $percentiles['p25'] }}</span>
                                <span class="mx-1 text-white/20">·</span>
                                <span class="text-gray-300 font-medium">P50: {{ $percentiles['p50'] }}</span>
                                <span class="mx-1 text-white/20">·</span>
                                <span class="text-gray-400">P75: {{ $percentiles['p75'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            @else
                <div class="px-6 py-8 text-center text-sm text-gray-400">
                    Loading data preview…
                </div>
            @endif
        </div>

        <div class="flex items-center justify-between">
            <button wire:click="prevStep" class="text-sm text-gray-400 hover:text-white transition-colors">← Back</button>
            <button wire:click="generate"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold rounded-md transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/>
                </svg>
                Generate AI report
            </button>
        </div>
    </div>
    @endif

    {{-- ═══ STEP 3: Generated report ═══ --}}
    @if($step === 3)
        @if($report && $report->isGenerating())
        <div class="bg-[#161b22] border border-white/10 rounded-lg px-6 py-12 text-center">
            <div class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-brand-500/15 border border-brand-500/20 mb-5">
                <svg class="h-7 w-7 text-brand-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-white">Generating your report…</p>
            <p class="text-xs text-gray-400 mt-1">The AI analyst is reading your data and writing the narrative. This takes 30–90 seconds.</p>
            <div class="flex items-center justify-center gap-1.5 mt-4">
                <span class="h-1.5 w-1.5 rounded-full bg-brand-400 animate-bounce" style="animation-delay:0ms"></span>
                <span class="h-1.5 w-1.5 rounded-full bg-brand-400 animate-bounce" style="animation-delay:150ms"></span>
                <span class="h-1.5 w-1.5 rounded-full bg-brand-400 animate-bounce" style="animation-delay:300ms"></span>
            </div>
        </div>

        @elseif($report && $report->status === 'failed')
        <div class="bg-red-500/15 border border-red-500/20 rounded-lg px-6 py-8 text-center">
            <p class="text-sm font-semibold text-red-400">Report generation failed</p>
            <p class="text-xs text-red-400/70 mt-1">{{ $report->metadata['error'] ?? 'Unknown error.' }}</p>
            <button wire:click="generate" class="mt-4 px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold rounded-md transition-colors">
                Try again
            </button>
        </div>

        @elseif($report && $report->isReady())
        <div class="space-y-4">
            {{-- Metadata bar --}}
            <div class="flex items-center gap-3 flex-wrap text-xs text-gray-400 font-mono bg-white/5 border border-white/10 rounded-lg px-4 py-3">
                <span>{{ $report->periodLabel() }}</span>
                <span>·</span>
                <span>{{ $report->metadata['model'] ?? 'AI' }}</span>
                @if($report->metadata['word_count'] ?? null)
                    <span>·</span>
                    <span>{{ number_format($report->metadata['word_count']) }} words</span>
                @endif
                <span>·</span>
                <span class="text-green-400 font-semibold">Ready</span>
            </div>

            {{-- Executive Summary --}}
            @if($report->executive_summary)
            <div class="bg-[#161b22] border border-white/10 rounded-lg overflow-hidden">
                <div class="px-5 py-3 border-b border-white/8 bg-white/5">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Executive Summary</span>
                </div>
                <div class="px-5 py-5 text-sm text-gray-300 leading-relaxed whitespace-pre-line">{{ $report->executive_summary }}</div>
            </div>
            @endif

            {{-- Full Report --}}
            @if(!empty($report->sections['full_narrative']))
            <div class="bg-[#161b22] border border-white/10 rounded-lg overflow-hidden">
                <div class="px-5 py-3 border-b border-white/8 bg-white/5">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Full Analysis</span>
                </div>
                <div class="px-5 py-5 text-sm text-gray-300 leading-relaxed whitespace-pre-line">{{ $report->sections['full_narrative'] }}</div>
            </div>
            @endif

            {{-- Recommendations --}}
            @if(!empty($report->recommendations))
            <div class="bg-[#161b22] border border-white/10 rounded-lg overflow-hidden">
                <div class="px-5 py-3 border-b border-white/8 bg-white/5">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Recommendations</span>
                </div>
                <div class="divide-y divide-white/8">
                    @foreach($report->recommendations as $i => $rec)
                    <div class="flex gap-4 px-5 py-4">
                        <div class="flex-shrink-0 h-5 w-5 rounded border border-brand-500/30 bg-brand-500/15 flex items-center justify-center">
                            <span class="text-[10px] font-bold text-brand-400">{{ $i + 1 }}</span>
                        </div>
                        <p class="text-sm text-gray-300 leading-relaxed">{{ $rec }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Actions --}}
            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ route('reports') }}" class="text-sm text-gray-400 hover:text-white transition-colors">← All reports</a>
                <a href="{{ route('report-pdf', $report->id) }}" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 border border-white/10 text-gray-300 text-sm rounded-md hover:bg-white/5 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    Export PDF
                </a>
            </div>
        </div>
        @endif
    @endif

</div>
