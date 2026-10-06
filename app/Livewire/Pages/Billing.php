<?php

namespace App\Livewire\Pages;

use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Billing extends Component
{
    public ?string $selectedPlan = null;
    public string $billingInterval = 'monthly';

    public function getPlansProperty()
    {
        return SubscriptionPlan::active()->get();
    }

    public function getActivePlanProperty(): ?object
    {
        $tenant = Auth::user()->tenant ?? null;
        if (!$tenant) return null;

        try {
            return $tenant->subscribed() ? $tenant->subscription() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function selectPlan(string $slug): void
    {
        $this->selectedPlan = $slug;
    }

    public function subscribe(): void
    {
        if (!config('cashier.key') && !config('services.stripe.key')) {
            session()->flash('info', 'Stripe integration requires STRIPE_KEY configuration. Contact your administrator to enable billing.');
            return;
        }

        $plan = SubscriptionPlan::where('slug', $this->selectedPlan)->firstOrFail();

        if (!$plan->stripe_price_id) {
            session()->flash('info', 'This plan is not yet configured for online checkout. Please contact us to subscribe.');
            return;
        }

        $tenant = Auth::user()->tenant;
        $checkout = $tenant->newSubscription('default', $plan->stripe_price_id)
            ->checkout([
                'success_url' => url('/billing?subscribed=1'),
                'cancel_url'  => url('/billing'),
            ]);

        $this->redirect($checkout->url);
    }

    public function render()
    {
        return view('livewire.pages.billing')->layout('layouts.app');
    }
}
