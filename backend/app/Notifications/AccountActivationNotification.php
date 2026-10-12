<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public ?string $activationUrl = null
    ) {
        $baseUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $this->activationUrl = $this->activationUrl ?: ($baseUrl . '/activate-account?token=' . urlencode($this->token));
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
            ->subject('Activate Your Account - ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your account has been created by the system administrator.')
            ->line('Please click the button below to verify your email address and activate your account:')
            ->action('Activate Account', $this->activationUrl)
            ->line('If you did not expect this invitation, please disregard this email.')
            ->salutation('Regards, ' . config('app.name') . ' Team');
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
