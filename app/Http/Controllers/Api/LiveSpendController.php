<?php

namespace App\Http\Controllers\Api;

use App\Services\LiveSpendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiveSpendController
{
    public function batch(Request $request)
    {
        $validated = $request->validate([
            'account_ids' => 'required|array',
            'account_ids.*' => 'integer',
        ]);

        $accountIds = $validated['account_ids'];

        Log::info('Live spend batch request', [
            'account_ids' => $accountIds,
            'user_id' => auth()->id(),
        ]);

        $results = app(LiveSpendService::class)->fetchBatch($accountIds);

        return response()->json($results);
    }
}
