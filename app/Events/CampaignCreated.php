<?php

namespace App\Events;

use App\Models\AdCampaign;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public AdCampaign $campaign;

    /**
     * Create a new event instance.
     */
    public function __construct(AdCampaign $campaign)
    {
        $this->campaign = $campaign;
    }
}