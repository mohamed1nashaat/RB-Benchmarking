<?php

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PerformanceAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Alert $alert;

    /**
     * Create a new notification instance.
     */
    public function __construct(Alert $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        // Check which channels are enabled for this alert
        if (in_array('email', $this->alert->notification_channels)) {
            $channels[] = 'mail';
        }

        // Future: Add Slack, WhatsApp, SMS channels here
        // if (in_array('slack', $this->alert->notification_channels)) {
        //     $channels[] = 'slack';
        // }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $alertType = ucfirst($this->alert->type);
        $alertName = $this->alert->name;

        $message = (new MailMessage)
            ->subject("🚨 {$alertType} Alert: {$alertName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your alert \"{$alertName}\" has been triggered.");

        // Add specific details based on alert type
        $message = $this->addAlertDetails($message);

        $message->action('View Dashboard', url('/'))
            ->line('Stay on top of your ad performance with Adintel.');

        return $message;
    }

    /**
     * Add alert-specific details to the email
     */
    protected function addAlertDetails(MailMessage $message): MailMessage
    {
        $conditions = $this->alert->conditions;

        switch ($this->alert->type) {
            case 'threshold':
                $metric = $conditions['metric'] ?? 'Unknown';
                $operator = $conditions['operator'] ?? '';
                $value = $conditions['value'] ?? '';
                $period = $conditions['period'] ?? '';

                $message->line("**Alert Type:** Threshold Alert")
                    ->line("**Metric:** " . strtoupper($metric))
                    ->line("**Condition:** {$operator} {$value}")
                    ->line("**Period:** " . ucfirst(str_replace('_', ' ', $period)));

                if (isset($conditions['scope']) && $conditions['scope'] !== 'all') {
                    $message->line("**Scope:** " . ucfirst($conditions['scope']));
                }
                break;

            case 'budget':
                $budget = $conditions['budget'] ?? 0;
                $period = $conditions['period'] ?? 'daily';
                $threshold = $conditions['threshold'] ?? 90;

                $message->line("**Alert Type:** Budget Alert")
                    ->line("**Budget:** {$budget} SAR")
                    ->line("**Period:** " . ucfirst($period))
                    ->line("**Threshold:** {$threshold}% of budget");
                break;

            case 'anomaly':
                $message->line("**Alert Type:** Anomaly Detection")
                    ->line("An unusual pattern has been detected in your campaign performance.");
                break;
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'alert_id' => $this->alert->id,
            'alert_name' => $this->alert->name,
            'alert_type' => $this->alert->type,
            'conditions' => $this->alert->conditions,
            'triggered_at' => now()->toDateTimeString(),
        ];
    }
}
