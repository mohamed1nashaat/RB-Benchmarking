<?php

namespace Tests\Unit;

use App\Livewire\Pages\ClientDashboard;
use PHPUnit\Framework\TestCase;

class PromptSortTest extends TestCase
{
    private function prompts()
    {
        $p = fn ($id, $cats, $vol, $vis, $brands) => ['prompt' => $id, 'categories' => $cats, 'topic_volume' => $vol, 'topic_visibility' => $vis, 'mentioned_brands_count' => $brands];

        return collect([
            $p('a', 'cites_target', 500, 0, 3),
            $p('b', 'mentions_target;cites_target', 100, 20, 1),
            $p('c', 'mentions_target', 900, 10, 8),
            $p('d', 'mentions_target;cites_target', 300, 5, 2),
        ]);
    }

    public function test_strength_comes_from_the_semrush_categories(): void
    {
        $this->assertSame('strong', ClientDashboard::promptStrength(['categories' => 'mentions_target;cites_target']));
        $this->assertSame('mentioned', ClientDashboard::promptStrength(['categories' => 'mentions_target']));
        $this->assertSame('cited', ClientDashboard::promptStrength(['categories' => 'cites_target']));
        $this->assertSame('none', ClientDashboard::promptStrength(['categories' => '']));
    }

    public function test_sort_orders(): void
    {
        $order = fn ($sort) => ClientDashboard::sortPrompts($this->prompts(), $sort)->pluck('prompt')->implode('');

        $this->assertSame('dbca', $order('strongest'));
        $this->assertSame('acdb', $order('weakest'));
        $this->assertSame('cadb', $order('volume_desc'));
        $this->assertSame('bdac', $order('volume_asc'));
        $this->assertSame('bcda', $order('visibility_desc'));
        $this->assertSame('adcb', $order('visibility_asc'));
        $this->assertSame('cadb', $order('brands_desc'));
        $this->assertSame('bdac', $order('brands_asc'));
        $this->assertArrayNotHasKey('_tier', ClientDashboard::sortPrompts($this->prompts(), 'strongest')->first());
    }
}
