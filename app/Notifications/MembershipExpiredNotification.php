<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipExpiredNotification extends Notification
{
    use Queueable;
    private $membership;

    /**
     * Create a new notification instance.
     */
    public function __construct($membership)
    {
        $this->membership = $membership;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        return (new MailMessage)
            ->subject('Membership Expired | Webflix')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your membership has expired.')
            ->line('Expiration Date: ' . $this->membership->end_date->format('d-m-Y'))
            ->action('Renew Membership', url('/subscription'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
