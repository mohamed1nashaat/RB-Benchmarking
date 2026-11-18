<?php

namespace App\Services\AiTracking;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Asks one AI assistant a question with live web search switched on, the way a
 * shopper would, and returns the answer text plus the sources it linked to.
 */
class AiEngineClient
{
    public const ENGINES = [
        'chatgpt' => ['label' => 'ChatGPT', 'config' => 'openai'],
        'gemini' => ['label' => 'Gemini', 'config' => 'gemini'],
        'perplexity' => ['label' => 'Perplexity', 'config' => 'perplexity'],
        // Not a consumer assistant: an open model on Ollama answering from Ollama web search.
        'ollama' => ['label' => 'Open model (Ollama)', 'config' => 'ollama', 'simulated' => true],
    ];

    /** True for engines that only imitate an assistant, so they are reported apart from the real ones. */
    public static function simulated(string $engine): bool
    {
        return (bool) (self::ENGINES[$engine]['simulated'] ?? false);
    }

    public static function label(string $engine): string
    {
        return self::ENGINES[$engine]['label'] ?? ucfirst($engine);
    }

    public static function configured(string $engine): bool
    {
        return isset(self::ENGINES[$engine]) && filled(config('services.ai_tracking.'.self::ENGINES[$engine]['config'].'.key'));
    }

    /** @return string[] engines that have an API key */
    public static function available(): array
    {
        return array_values(array_filter(array_keys(self::ENGINES), fn ($e) => self::configured($e)));
    }

    public static function model(string $engine): string
    {
        return (string) config('services.ai_tracking.'.self::ENGINES[$engine]['config'].'.model');
    }

    /** @return array{text: string, sources: array<int, array{url: string, domain: string, title: ?string}>, model: string} */
    public function ask(string $engine, string $prompt, ?string $country = null): array
    {
        if (! self::configured($engine)) {
            throw new \RuntimeException(self::label($engine).' has no API key.');
        }

        return match ($engine) {
            'chatgpt' => $this->openai($prompt, $country),
            'gemini' => $this->gemini($prompt),
            'perplexity' => $this->perplexity($prompt, $country),
            'ollama' => $this->ollama($prompt, $country),
        };
    }

    private function http()
    {
        return Http::timeout(120)->connectTimeout(15)->acceptJson()
            ->retry(2, 2000, fn ($e) => $e instanceof RequestException ? in_array($e->response->status(), [429, 500, 502, 503, 504], true) : true, throw: false);
    }

    private function openai(string $prompt, ?string $country): array
    {
        $tool = ['type' => 'web_search'];
        if ($country && strlen($country) === 2) {
            $tool['user_location'] = ['type' => 'approximate', 'country' => strtoupper($country)];
        }
        $model = self::model('chatgpt');
        $r = $this->http()->withToken(config('services.ai_tracking.openai.key'))
            ->post('https://api.openai.com/v1/responses', ['model' => $model, 'input' => $prompt, 'tools' => [$tool]])
            ->throw()->json();

        $text = '';
        $sources = [];
        foreach ($r['output'] ?? [] as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }
            foreach ($item['content'] ?? [] as $c) {
                if (($c['type'] ?? null) !== 'output_text') {
                    continue;
                }
                $text .= ($text === '' ? '' : "\n").($c['text'] ?? '');
                foreach ($c['annotations'] ?? [] as $a) {
                    if (($a['type'] ?? null) === 'url_citation' && ! empty($a['url'])) {
                        $sources[] = self::source($a['url'], $a['title'] ?? null);
                    }
                }
            }
        }

        return ['text' => $text, 'sources' => self::unique($sources), 'model' => $r['model'] ?? $model];
    }

    private function gemini(string $prompt): array
    {
        $model = self::model('gemini');
        $r = $this->http()->withHeaders(['x-goog-api-key' => config('services.ai_tracking.gemini.key')])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'tools' => [['google_search' => (object) []]],
            ])->throw()->json();

        $cand = $r['candidates'][0] ?? [];
        $text = collect($cand['content']['parts'] ?? [])->pluck('text')->filter()->implode("\n");
        $sources = [];
        foreach ($cand['groundingMetadata']['groundingChunks'] ?? [] as $chunk) {
            $web = $chunk['web'] ?? null;
            if (! $web || empty($web['uri'])) {
                continue;
            }
            // Gemini links go through a Google redirect; its title is the real site domain.
            $title = $web['title'] ?? null;
            $domain = $title && preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $title) ? strtolower($title) : null;
            $sources[] = self::source($web['uri'], $title, $domain);
        }

        return ['text' => $text, 'sources' => self::unique($sources), 'model' => $r['modelVersion'] ?? $model];
    }

    private function perplexity(string $prompt, ?string $country): array
    {
        $model = self::model('perplexity');
        $body = ['model' => $model, 'messages' => [['role' => 'user', 'content' => $prompt]]];
        if ($country && strlen($country) === 2) {
            $body['web_search_options'] = ['user_location' => ['country' => strtoupper($country)]];
        }
        $r = $this->http()->withToken(config('services.ai_tracking.perplexity.key'))
            ->post('https://api.perplexity.ai/chat/completions', $body)->throw()->json();

        $text = (string) ($r['choices'][0]['message']['content'] ?? '');
        $sources = [];
        foreach ($r['search_results'] ?? [] as $s) {
            if (! empty($s['url'])) {
                $sources[] = self::source($s['url'], $s['title'] ?? null);
            }
        }
        foreach ($r['citations'] ?? [] as $url) {
            if (is_string($url)) {
                $sources[] = self::source($url, null);
            }
        }

        return ['text' => $text, 'sources' => self::unique($sources), 'model' => $r['model'] ?? $model];
    }

    /**
     * Ollama has no assistant with built-in search, so search first with Ollama's web
     * search, then ask the model to answer from those results and cite them as [1], [2].
     * Only the results the answer actually cites count as sources.
     */
    private function ollama(string $prompt, ?string $country): array
    {
        $host = rtrim((string) config('services.ai_tracking.ollama.host', 'https://ollama.com'), '/');
        $key = config('services.ai_tracking.ollama.key');
        $model = self::model('ollama');

        $results = $this->http()->withToken($key)
            ->post($host.'/api/web_search', ['query' => $prompt, 'max_results' => 5])
            ->throw()->json('results') ?? [];
        $results = array_values(array_filter($results, fn ($r) => is_array($r) && ! empty($r['url'])));
        $context = collect($results)->map(fn ($r, $i) => '['.($i + 1).'] '.trim(($r['title'] ?? '').' '.$r['url'])."\n"
            .\Illuminate\Support\Str::limit(trim((string) ($r['content'] ?? '')), 1500))->implode("\n\n");

        $system = 'You are an AI shopping assistant answering a customer. Use the web results below where they help and cite them inline as [1], [2]. '
            .'Name specific brands and stores when you recommend them. Answer in the language of the question.'
            .($country && strlen($country) === 2 ? ' The customer is in '.strtoupper($country).'.' : '')
            ."\n\nWeb results:\n".($context !== '' ? $context : 'none');

        $r = $this->http()->withToken($key)->post($host.'/api/chat', [
            'model' => $model, 'stream' => false,
            'messages' => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $prompt]],
        ])->throw()->json();

        $text = (string) ($r['message']['content'] ?? '');
        preg_match_all('/\[(\d{1,2})\]/', $text, $m);
        $sources = [];
        foreach (array_unique($m[1]) as $n) {
            if (isset($results[(int) $n - 1])) {
                $sources[] = self::source($results[(int) $n - 1]['url'], $results[(int) $n - 1]['title'] ?? null);
            }
        }

        return ['text' => $text, 'sources' => self::unique($sources), 'model' => $r['model'] ?? $model];
    }

    public static function domainOf(string $url): string
    {
        $host = parse_url(str_contains($url, '://') ? $url : 'https://'.$url, PHP_URL_HOST) ?: $url;

        return preg_replace('/^www\./', '', strtolower($host));
    }

    private static function source(string $url, ?string $title, ?string $domain = null): array
    {
        return ['url' => $url, 'domain' => $domain ?? self::domainOf($url), 'title' => $title];
    }

    private static function unique(array $sources): array
    {
        return collect($sources)->unique('url')->values()->all();
    }
}
