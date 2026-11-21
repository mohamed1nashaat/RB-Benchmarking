<?php

namespace App\Services;

use InvalidArgumentException;

class SemrushExportParser
{
    public function parse(string $path, string $type): array
    {
        $required = match ($type) {
            'topics' => ['name', 'country', 'visibility', 'difficulty', 'mentions', 'volume', 'volume_trend', 'intents'],
            'prompts' => ['prompt', 'country', 'llm', 'categories', 'topic_id', 'topic_name', 'topic_volume', 'topic_intents', 'topic_visibility', 'topic_difficulty', 'brief_response', 'mentioned_brands_count', 'sources_count'],
            default => throw new InvalidArgumentException('Unknown export type.'),
        };
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new InvalidArgumentException('Cannot open export: '.basename($path));
        }
        try {
            $headers = fgetcsv($handle, 0, ',', '"', '');
            if (! $headers) {
                throw new InvalidArgumentException('Empty export.');
            }
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            if (array_diff($required, $headers)) {
                throw new InvalidArgumentException('Missing columns in '.basename($path));
            }
            $rows = [];
            while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if ($values === [null]) {
                    continue;
                }
                if (count($values) !== count($headers)) {
                    throw new InvalidArgumentException('Malformed CSV row in '.basename($path));
                }
                $row = array_combine($headers, $values);
                foreach (['visibility', 'difficulty', 'mentions', 'volume', 'topic_volume', 'topic_visibility', 'topic_difficulty', 'mentioned_brands_count', 'sources_count'] as $key) {
                    if (array_key_exists($key, $row)) {
                        if (! ctype_digit($row[$key])) {
                            throw new InvalidArgumentException('Invalid numeric value: '.$key);
                        }
                        $row[$key] = (int) $row[$key];
                    }
                }
                // Keep topic IDs as strings: some exceed signed 64-bit integer limits.
                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }
}
