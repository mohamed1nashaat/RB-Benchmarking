<?php

namespace Tests\Unit;

use App\Services\SemrushExportParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SemrushExportParserTest extends TestCase
{
    public function test_preserves_unicode_multiline_responses_and_large_topic_ids(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'semrush');
        $handle = fopen($path, 'w');
        fputcsv($handle, ['prompt', 'country', 'llm', 'categories', 'topic_id', 'topic_name', 'topic_volume', 'topic_intents', 'topic_visibility', 'topic_difficulty', 'brief_response', 'mentioned_brands_count', 'sources_count'], ',', '"', '');
        fputcsv($handle, ['عسل؟', 'sa', 'chatgpt', 'mentions_target;cites_target', '16761296487254763338', 'عسل', '100', 'informational:100', '50', '20', "First, quoted\nsecond line", '3', '2'], ',', '"', '');
        fclose($handle);
        try {
            $rows = (new SemrushExportParser)->parse($path, 'prompts');
            $this->assertSame('16761296487254763338', $rows[0]['topic_id']);
            $this->assertSame('عسل؟', $rows[0]['prompt']);
            $this->assertSame("First, quoted\nsecond line", $rows[0]['brief_response']);
            $this->assertSame(100, $rows[0]['topic_volume']);
        } finally {
            unlink($path);
        }
    }

    public function test_rejects_missing_columns(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'semrush');
        file_put_contents($path, "name,country\nHoney,sa\n");
        try {
            $this->expectException(InvalidArgumentException::class);
            (new SemrushExportParser)->parse($path, 'topics');
        } finally {
            unlink($path);
        }
    }
}
