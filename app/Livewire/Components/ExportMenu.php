<?php

namespace App\Livewire\Components;

use Livewire\Component;

class ExportMenu extends Component
{
    public string $elementId = 'benchmark-report';
    public string $filename = 'benchmark-report';
    public bool $loading = false;

    public function render()
    {
        return view('livewire.components.export-menu');
    }
}
