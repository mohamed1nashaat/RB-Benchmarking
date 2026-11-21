<?php

namespace App\Livewire\Components;

use Livewire\Component;

class AiChat extends Component
{
    public array  $messages    = [];
    public string $input       = '';
    public bool   $isStreaming = false;
    public bool   $open        = false;
    public string $platform    = 'facebook';
    public string $industry    = 'e_commerce_retail';

    public function toggleOpen(): void
    {
        $this->open = !$this->open;
    }

    public function clearHistory(): void
    {
        $this->messages    = [];
        $this->isStreaming = false;
    }

    public function sendMessage(): void
    {
        $this->input = trim($this->input);
        if ($this->input === '' || $this->isStreaming) {
            return;
        }

        $this->messages[] = [
            'role'    => 'user',
            'content' => $this->input,
        ];

        // Placeholder for the assistant reply — Alpine will fill this in via SSE
        $this->messages[] = [
            'role'      => 'assistant',
            'content'   => '',
            'streaming' => true,
        ];

        $this->isStreaming = true;

        $history = array_filter(
            array_slice($this->messages, 0, -1),   // exclude the placeholder
            fn($m) => !($m['streaming'] ?? false)
        );
        $history = array_values($history);

        $this->dispatch('ai-send-message', [
            'message'  => $this->input,
            'history'  => array_slice($history, -10),  // last 10 turns
            'context'  => [
                'platform' => $this->platform,
                'industry' => $this->industry,
            ],
        ]);

        $this->input = '';
    }

    public function onStreamComplete(): void
    {
        $this->isStreaming = false;

        // Remove the streaming flag from the last message
        $last = count($this->messages) - 1;
        if (isset($this->messages[$last]['streaming'])) {
            unset($this->messages[$last]['streaming']);
        }
    }

    public function updateLastMessage(string $content): void
    {
        $last = count($this->messages) - 1;
        if ($last >= 0) {
            $this->messages[$last]['content'] = $content;
        }
    }

    public function render()
    {
        return view('livewire.components.ai-chat');
    }
}
