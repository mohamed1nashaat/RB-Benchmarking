<?php

namespace App\Livewire\Components;

use Livewire\Component;

class LanguageSwitcher extends Component
{
    public string $currentLocale;

    public function mount()
    {
        $this->currentLocale = app()->getLocale();
    }

    public function switchLocale(string $locale)
    {
        if (!in_array($locale, ['en', 'ar'])) {
            return;
        }

        session(['locale' => $locale]);
        app()->setLocale($locale);
        $this->currentLocale = $locale;

        // Full page redirect to apply RTL/LTR change
        $referer = request()->header('Referer');
        $fallback = '/';

        // Only redirect to same-origin URLs to prevent open redirect attacks
        if ($referer && str_starts_with($referer, config('app.url'))) {
            return $this->redirect($referer, navigate: false);
        }

        return $this->redirect($fallback, navigate: false);
    }

    public function render()
    {
        return view('livewire.components.language-switcher');
    }
}
