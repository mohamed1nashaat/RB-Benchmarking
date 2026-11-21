<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PageSpeedService
{
    protected function getApiKey(): ?string
    {
        return config('services.pagespeed.api_key') ?: null;
    }

    /**
     * Analyze a URL with PageSpeed Insights.
     * Results are cached for 24 hours.
     */
    public function analyze(string $url, string $strategy = 'mobile'): ?array
    {
        $cacheKey = 'pagespeed:' . md5($url . ':' . $strategy);

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            // Serve cached error markers as-is (prevents hammering a quota-exceeded API)
            if (isset($cached['_error'])) {
                return $cached;
            }
            // Don't serve cached empty results — treat as cache miss
            if ($this->hasMeaningfulData($cached)) {
                return $cached;
            }
            Cache::forget($cacheKey);
        }

        $result = $this->fetchAnalysis($url, $strategy);
        if ($result !== null && $this->hasMeaningfulData($result)) {
            Cache::put($cacheKey, $result, 24 * 3600);
        } elseif ($result !== null && isset($result['_error'])) {
            // Cache error results for 10 minutes to avoid hammering the API
            Cache::put($cacheKey, $result, 600);
        }
        return $result;
    }

    protected function fetchAnalysis(string $url, string $strategy): ?array
    {
        try {
            $query = http_build_query([
                'url' => $url,
                'strategy' => $strategy,
            ]);
            // Append category params individually (Google expects repeated keys, not array notation)
            foreach (['performance', 'seo', 'accessibility', 'best-practices'] as $cat) {
                $query .= '&' . urlencode('category') . '=' . urlencode($cat);
            }
            $apiKey = $this->getApiKey();
            if ($apiKey) {
                $query .= '&key=' . urlencode($apiKey);
            }

            $response = Http::timeout(60)->get(
                'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?' . $query
            );

            if (!$response->successful()) {
                Log::warning('PageSpeed API failed', [
                    'url' => $url,
                    'strategy' => $strategy,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 300),
                ]);
                // Return a typed error so the UI can show a specific message
                if ($response->status() === 429) {
                    return ['_error' => 'quota_exceeded'];
                }
                if ($response->status() === 403) {
                    $reason = $response->json()['error']['errors'][0]['reason'] ?? null;
                    if (in_array($reason, ['dailyLimitExceeded', 'rateLimitExceeded', 'userRateLimitExceeded'])) {
                        return ['_error' => 'quota_exceeded'];
                    }
                    return ['_error' => 'api_forbidden'];
                }
                return ['_error' => 'api_error'];
            }

            $data = $response->json();

            $auditsCount = count($data['lighthouseResult']['audits'] ?? []);
            $categoriesCount = count($data['lighthouseResult']['categories'] ?? []);
            if ($auditsCount === 0 || $categoriesCount === 0) {
                Log::warning('PageSpeed API returned incomplete data', [
                    'url' => $url,
                    'strategy' => $strategy,
                    'audits_count' => $auditsCount,
                    'categories_count' => $categoriesCount,
                ]);
            }

            return $this->parseResponse($data);
        } catch (\Exception $e) {
            Log::error('PageSpeed analyze error', [
                'url' => $url,
                'strategy' => $strategy,
                'error' => $e->getMessage(),
            ]);
            return ['_error' => 'api_error'];
        }
    }

    protected function parseResponse(array $data): array
    {
        $lighthouseResult = $data['lighthouseResult'] ?? [];
        $categories = $lighthouseResult['categories'] ?? [];
        $audits = $lighthouseResult['audits'] ?? [];

        $scores = [
            'performance' => $this->extractScore($categories, 'performance'),
            'seo' => $this->extractScore($categories, 'seo'),
            'accessibility' => $this->extractScore($categories, 'accessibility'),
            'best_practices' => $this->extractScore($categories, 'best-practices'),
        ];

        $coreWebVitals = [
            'lcp' => $this->extractMetric($audits, 'largest-contentful-paint'),
            'fid' => $this->extractMetric($audits, 'max-potential-fid'),
            'cls' => $this->extractMetric($audits, 'cumulative-layout-shift'),
            'fcp' => $this->extractMetric($audits, 'first-contentful-paint'),
            'tbt' => $this->extractMetric($audits, 'total-blocking-time'),
            'tti' => $this->extractMetric($audits, 'interactive'),
            'ttfb' => $this->extractMetric($audits, 'server-response-time'),
            'si' => $this->extractMetric($audits, 'speed-index'),
        ];

        return [
            'scores' => $scores,
            'core_web_vitals' => $coreWebVitals,
            'audits' => $this->extractSeoAudits($audits),
        ];
    }

    protected function extractScore(array $categories, string $key): int
    {
        return isset($categories[$key]['score'])
            ? (int) round($categories[$key]['score'] * 100)
            : 0;
    }

    protected function extractMetric(array $audits, string $key): array
    {
        $audit = $audits[$key] ?? [];

        return [
            'value' => $audit['numericValue'] ?? null,
            'display' => $audit['displayValue'] ?? '-',
            'score' => isset($audit['score']) ? $this->mapScoreToStatus($audit['score']) : 'unknown',
        ];
    }

    protected function mapScoreToStatus(?float $score): string
    {
        if ($score === null) return 'unknown';
        if ($score >= 0.9) return 'good';
        if ($score >= 0.5) return 'needs_improvement';
        return 'poor';
    }

    /**
     * Extract only the curated SEO-relevant Lighthouse audits.
     * Returns [audit_id => ['score' => float|null, 'title' => string]]
     */
    protected function extractSeoAudits(array $audits): array
    {
        $curatedIds = [
            'is-crawlable', 'http-status-code', 'robots-txt',
            'meta-description', 'document-title', 'canonical',
            'hreflang', 'crawlable-anchors', 'structured-data',
            'image-alt', 'link-text', 'heading-order',
            'tap-targets', 'font-size',
            'render-blocking-resources', 'total-byte-weight',
        ];

        $extracted = [];
        foreach ($curatedIds as $id) {
            if (isset($audits[$id])) {
                $extracted[$id] = [
                    'score' => $audits[$id]['score'] ?? null,
                    'title' => $audits[$id]['title'] ?? $id,
                ];
            }
        }

        return $extracted;
    }

    protected function hasMeaningfulData(array $result): bool
    {
        $scores = $result['scores'] ?? [];
        return ($scores['performance'] ?? 0) > 0
            || ($scores['seo'] ?? 0) > 0
            || ($scores['accessibility'] ?? 0) > 0
            || ($scores['best_practices'] ?? 0) > 0;
    }
}
