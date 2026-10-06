<?php

namespace App\Services;

use App\Exceptions\OllamaException;
use App\Models\AdAccount;
use App\Models\AdMetric;
use App\Models\IndustryBenchmark;
use Generator;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    private string $host;
    private string $model;
    private string $embedModel;

    public function __construct()
    {
        $this->host       = rtrim(config('services.ollama.host', 'http://localhost:11434'), '/');
        $this->model      = config('services.ollama.model', 'llama3.1:70b');
        $this->embedModel = config('services.ollama.embed_model', 'nomic-embed-text');
    }

    /**
     * Embed a text string. Returns a 768-dim float array.
     * Throws OllamaException on failure (caller must handle for pipeline).
     */
    public function embed(string $text): array
    {
        $response = $this->request('/api/embeddings', [
            'model'  => $this->embedModel,
            'prompt' => $text,
        ]);

        if (!isset($response['embedding']) || !is_array($response['embedding'])) {
            throw new OllamaException('Ollama embedding response missing embedding field.');
        }

        return $response['embedding'];
    }

    /**
     * Single-shot chat. Returns the full assistant reply as a string.
     * Returns empty string and logs on failure (non-embed callers get graceful degradation).
     */
    public function chat(string $systemPrompt, array $messages): string
    {
        try {
            $response = $this->request('/api/chat', [
                'model'    => $this->model,
                'stream'   => false,
                'messages' => array_merge(
                    [['role' => 'system', 'content' => $systemPrompt]],
                    $messages
                ),
            ]);

            return $response['message']['content'] ?? '';
        } catch (\Throwable $e) {
            Log::warning('OllamaService::chat failed', ['error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Streaming chat. Yields string content chunks as they arrive.
     * Yields an empty string and logs on connection failure.
     */
    public function streamChat(string $systemPrompt, array $messages): Generator
    {
        $payload = json_encode([
            'model'    => $this->model,
            'stream'   => true,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $messages
            ),
        ]);

        $ch = curl_init($this->host . '/api/chat');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $buffer = '';
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$buffer) {
            $buffer .= $data;
            return strlen($data);
        });

        // We can't yield inside a CURLOPT_WRITEFUNCTION callback, so we collect
        // the full streamed body and re-parse it line by line after execution.
        // For true real-time streaming from a controller, use the raw SSE approach below.
        $error = false;
        try {
            curl_exec($ch);
            if (curl_errno($ch)) {
                throw new \RuntimeException(curl_error($ch));
            }
        } catch (\Throwable $e) {
            Log::warning('OllamaService::streamChat connection failed', ['error' => $e->getMessage()]);
            $error = true;
        } finally {
            curl_close($ch);
        }

        if ($error) {
            yield '';
            return;
        }

        foreach (explode("\n", $buffer) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $decoded = json_decode($line, true);
            if (isset($decoded['message']['content'])) {
                yield $decoded['message']['content'];
            }
            if (!empty($decoded['done'])) {
                break;
            }
        }
    }

    /**
     * Stream directly to PHP output (for use in a controller with ob_implicit_flush).
     * Writes SSE-formatted chunks. Returns false if Ollama is unreachable.
     */
    public function streamToOutput(string $systemPrompt, array $messages): bool
    {
        $payload = json_encode([
            'model'    => $this->model,
            'stream'   => true,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $messages
            ),
        ]);

        $ch = curl_init($this->host . '/api/chat');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $lineBuffer = '';
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$lineBuffer) {
            $lineBuffer .= $data;
            while (($pos = strpos($lineBuffer, "\n")) !== false) {
                $line = substr($lineBuffer, 0, $pos);
                $lineBuffer = substr($lineBuffer, $pos + 1);
                $decoded = json_decode($line, true);
                if (isset($decoded['message']['content']) && $decoded['message']['content'] !== '') {
                    echo 'data: ' . json_encode(['chunk' => $decoded['message']['content']]) . "\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            }
            return strlen($data);
        });

        curl_exec($ch);
        $err = curl_errno($ch) ? curl_error($ch) : null;
        curl_close($ch);

        if ($err) {
            Log::warning('OllamaService::streamToOutput failed', ['error' => $err]);
            return false;
        }

        return true;
    }

    /**
     * Build a rich prompt context string for a tenant+industry+platform combination.
     */
    public function buildAccountContext(int $tenantId, string $industry, string $platform): string
    {
        $since = now()->subDays(30)->toDateString();

        // Aggregate last-30-day metrics for this tenant + platform
        $metrics = AdMetric::query()
            ->where('tenant_id', $tenantId)
            ->where('platform', $platform)
            ->where('date', '>=', $since)
            ->selectRaw('
                SUM(spend)       as total_spend,
                SUM(impressions) as total_impressions,
                SUM(clicks)      as total_clicks,
                SUM(leads)       as total_leads,
                SUM(conversions) as total_conversions,
                COUNT(DISTINCT ad_campaign_id) as campaign_count
            ')
            ->first();

        // Industry benchmarks
        $benchmarks = IndustryBenchmark::query()
            ->where('industry', $industry)
            ->where('platform', $platform)
            ->get()
            ->keyBy('metric');

        $spend       = (float) ($metrics->total_spend ?? 0);
        $impressions = (int)   ($metrics->total_impressions ?? 0);
        $clicks      = (int)   ($metrics->total_clicks ?? 0);
        $leads       = (int)   ($metrics->total_leads ?? 0);
        $campaigns   = (int)   ($metrics->campaign_count ?? 0);

        $ctr  = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
        $cpc  = $clicks > 0      ? round($spend / $clicks, 2) : 0;
        $cpm  = $impressions > 0 ? round(($spend / $impressions) * 1000, 2) : 0;
        $cpl  = $leads > 0       ? round($spend / $leads, 2) : 0;

        $lines = [
            "=== ACCOUNT PERFORMANCE (Last 30 Days) ===",
            "Platform: {$platform}  |  Industry: {$industry}",
            "Active campaigns: {$campaigns}",
            "Total spend: SAR " . number_format($spend, 2),
            "Impressions: " . number_format($impressions),
            "Clicks: " . number_format($clicks),
            "CTR: {$ctr}%",
            "CPC: SAR {$cpc}",
            "CPM: SAR {$cpm}",
            "Leads / Conversions: " . number_format($leads),
            $leads > 0 ? "CPL: SAR {$cpl}" : "CPL: N/A",
        ];

        if ($benchmarks->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "=== INDUSTRY BENCHMARKS (P50 median) ===";
            foreach ($benchmarks as $metric => $bm) {
                $lines[] = strtoupper($metric) . ": P25=" . $bm->percentile_25 . "  P50=" . $bm->percentile_50 . "  P75=" . $bm->percentile_75;
            }

            // Highlight deviations
            $lines[] = "";
            $lines[] = "=== BENCHMARK COMPARISON ===";
            if (isset($benchmarks['ctr'])) {
                $p50 = (float) $benchmarks['ctr']->percentile_50;
                $status = $ctr >= $p50 ? 'ABOVE' : 'BELOW';
                $lines[] = "CTR {$ctr}% is {$status} industry median ({$p50}%)";
            }
            if (isset($benchmarks['cpl']) && $cpl > 0) {
                $p50 = (float) $benchmarks['cpl']->percentile_50;
                $status = $cpl <= $p50 ? 'BETTER THAN' : 'WORSE THAN';
                $lines[] = "CPL SAR {$cpl} is {$status} industry median (SAR {$p50})";
            }
        }

        $lines[] = "";
        $lines[] = "Currency: SAR. Market: MENA (primarily Saudi Arabia). Today: " . now()->format('Y-m-d') . ".";

        return implode("\n", $lines);
    }

    /**
     * Generic cURL request to Ollama API. Returns decoded JSON array.
     * Throws \RuntimeException on network/parse failure.
     */
    private function request(string $endpoint, array $data): array
    {
        $ch = curl_init($this->host . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno || $body === false) {
            throw new \RuntimeException("Ollama connection failed: {$error}");
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Ollama returned invalid JSON: " . substr($body, 0, 200));
        }

        return $decoded;
    }
}
