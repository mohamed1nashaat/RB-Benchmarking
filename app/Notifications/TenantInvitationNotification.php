<?php

namespace App\Notifications;

use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected UserInvitation $invitation;

    /**
     * Create a new notification instance.
     */
    public function __construct(UserInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $tenantName = $this->invitation->tenant->name;
        $inviterName = $this->invitation->inviter->name;
        $role = ucfirst($this->invitation->role);
        $acceptUrl = url("/invitation/{$this->invitation->token}");
        $expiresAt = $this->invitation->expires_at->format('F j, Y \a\t g:i A');

        return (new MailMessage)
            ->subject("You've been invited to join {$tenantName} on RB Benchmarks")
            ->greeting("Hello!")
            ->line("{$inviterName} has invited you to join **{$tenantName}** as a **{$role}** on RB Benchmarks.")
            ->line("RB Benchmarks is an advanced advertising performance analytics platform.")
            ->action('Accept Invitation', $acceptUrl)
            ->line("This invitation will expire on {$expiresAt}.")
            ->line("If you didn't expect this invitation, you can safely ignore this email.");
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'tenant_id' => $this->invitation->tenant_id,
            'tenant_name' => $this->invitation->tenant->name,
            'role' => $this->invitation->role,
            'invited_by' => $this->invitation->invited_by,
            'expires_at' => $this->invitation->expires_at->toDateTimeString(),
        ];
    }
}
