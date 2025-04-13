<?php

namespace App\Notifications;

use App\Models\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimBatchNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Batch $batch)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Batch is ready')
            ->greeting('Hello '.$notifiable->batch_identifier.',')
            ->line('Thank you for using our service!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'batch_id' => $notifiable->batch_identifier,
            'message' => "Your claims #{$notifiable->batch_identifier} has reach threshold.",
            'url' => url("/batches/{$notifiable->id}"),
        ];
    }

    public function routeNotificationForMail(): string
    {
        return $this->batch->insurer->email;
    }
}
