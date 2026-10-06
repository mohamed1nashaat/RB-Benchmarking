<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class AiVector extends Model
{
    protected $fillable = [
        'tenant_id',
        'entity_type',
        'entity_id',
        'content',
        'vector',
        'model',
    ];

    protected $casts = [
        'vector'    => 'array',
        'entity_id' => 'integer',
        'tenant_id' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Find the top-K most similar vectors to a query vector for a given tenant.
     */
    public static function findSimilar(
        array  $queryVector,
        int    $tenantId,
        string $entityType = null,
        int    $topK = 8
    ): Collection {
        $query = static::query()->where('tenant_id', $tenantId);

        if ($entityType !== null) {
            $query->where('entity_type', $entityType);
        }

        $rows = $query->get(['id', 'entity_type', 'entity_id', 'content', 'vector']);

        return $rows
            ->map(function (AiVector $row) use ($queryVector) {
                return [
                    'id'          => $row->id,
                    'entity_type' => $row->entity_type,
                    'entity_id'   => $row->entity_id,
                    'content'     => $row->content,
                    'similarity'  => self::cosineSimilarity($queryVector, $row->vector),
                ];
            })
            ->sortByDesc('similarity')
            ->take($topK)
            ->values();
    }

    private static function cosineSimilarity(array $a, array $b): float
    {
        if (empty($a) || empty($b)) {
            return 0.0;
        }

        $dot  = 0.0;
        $magA = 0.0;
        $magB = 0.0;

        $len = min(count($a), count($b));
        for ($i = 0; $i < $len; $i++) {
            $dot  += $a[$i] * $b[$i];
            $magA += $a[$i] * $a[$i];
            $magB += $b[$i] * $b[$i];
        }

        $denom = sqrt($magA) * sqrt($magB);
        if ($denom == 0.0) {
            return 0.0;
        }

        return $dot / $denom;
    }
}
