<?php

namespace App\Http\Controllers;

use App\Services\OllamaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiChatController extends Controller
{
    public function stream(Request $request, OllamaService $ollama)
    {
        $validated = $request->validate([
            'message'            => 'required|string|max:2000',
            'history'            => 'nullable|array|max:20',
            'history.*.role'     => 'required_with:history|in:user,assistant',
            'history.*.content'  => 'required_with:history|string|max:4000',
            'context.platform'   => 'nullable|string|max:64',
            'context.industry'   => 'nullable|string|max:128',
        ]);

        $user       = auth()->user();
        $tenantId   = $user->tenant_id ?? null;
        $platform   = $validated['context']['platform'] ?? 'facebook';
        $industry   = $validated['context']['industry'] ?? 'e_commerce_retail';

        $systemPrompt = $this->buildSystemPrompt($ollama, $tenantId, $industry, $platform);

        $messages   = $validated['history'] ?? [];
        $messages[] = ['role' => 'user', 'content' => $validated['message']];

        return response()->stream(function () use ($ollama, $systemPrompt, $messages) {
            // Disable output buffering so chunks reach the client immediately
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            @ini_set('zlib.output_compression', 0);

            $success = $ollama->streamToOutput($systemPrompt, $messages);

            if (!$success) {
                echo 'data: ' . json_encode(['chunk' => "I'm sorry, the AI engine is currently unavailable. Please try again later."]) . "\n\n";
                flush();
            }

            echo "data: [DONE]\n\n";
            flush();
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    private function buildSystemPrompt(OllamaService $ollama, ?int $tenantId, string $industry, string $platform): string
    {
        $base = <<<EOT
You are an expert digital marketing analyst specializing in MENA (Middle East & North Africa) advertising, with deep expertise in performance marketing in Saudi Arabia and the Gulf region.

Your role:
- Analyze campaign performance data and provide actionable insights
- Compare metrics against MENA industry benchmarks (you have access to real benchmark data)
- Provide specific, data-driven recommendations with expected SAR impact
- Speak plainly and directly — no hedging, no vague generalities
- Always reference specific numbers from the context provided
- Currency is Saudi Riyal (SAR). Platforms: Facebook/Meta, Google Ads, TikTok, Snapchat, LinkedIn

When asked to build a media plan or analyze performance, be specific: name the platform, suggest exact budget splits, cite the relevant benchmark percentile.
EOT;

        if ($tenantId) {
            try {
                $accountContext = $ollama->buildAccountContext($tenantId, $industry, $platform);
                return $base . "\n\n" . $accountContext;
            } catch (\Throwable $e) {
                Log::warning('AiChatController: could not build account context', ['error' => $e->getMessage()]);
            }
        }

        $base .= "\n\nToday: " . now()->format('Y-m-d') . ". Market: MENA / Saudi Arabia.";
        return $base;
    }
}
