<?php

namespace App\Console\Commands;

use App\Models\AiTrackingProfile;
use App\Services\AiTracking\AiEngineClient;
use App\Services\AiTracking\PromptTracker;
use Illuminate\Console\Command;

class TrackAiPrompts extends Command
{
    protected $signature = 'ai:track-prompts {--tenant=* : Only these client IDs} {--engine=* : chatgpt, gemini or perplexity} {--limit= : Max prompts per client} {--force : Ask again even if already asked this week} {--seed : Copy Semrush prompts in first}';

    protected $description = 'Ask each client\'s tracked prompts to ChatGPT, Gemini and Perplexity and record mentions, rank and citations';

    public function handle(PromptTracker $tracker): int
    {
        $available = AiEngineClient::available();
        if (! $available) {
            $this->warn('No AI engine has an API key. Set OPENAI_API_KEY, GEMINI_API_KEY or PERPLEXITY_API_KEY in .env.');
        }

        if ($this->option('seed')) {
            foreach ($this->option('tenant') as $id) {
                $this->info("Client {$id}: added ".$tracker->seedFromSemrush((int) $id, (int) ($this->option('limit') ?: config('services.ai_tracking.max_prompts', 50))).' Semrush prompts.');
            }
        }
        if (! $available) {
            return self::SUCCESS;
        }

        $profiles = AiTrackingProfile::where('active', true)
            ->when($this->option('tenant'), fn ($q, $ids) => $q->whereIn('tenant_id', $ids))->get();

        foreach ($profiles as $profile) {
            $engines = $tracker->enginesFor($profile, $this->option('engine'));
            $this->line("Client {$profile->tenant_id}: ".implode(', ', array_map([AiEngineClient::class, 'label'], $engines)));
            $stats = $tracker->run($profile, $this->option('engine'), (bool) $this->option('force'), $this->option('limit') ? (int) $this->option('limit') : null);
            $this->info("  asked {$stats['asked']}, already done this week {$stats['skipped']}, failed {$stats['failed']}");
        }

        return self::SUCCESS;
    }
}
