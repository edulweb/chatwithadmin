<?php

namespace App\Notifications;

use App\Support\UrlHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyNewEmailNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public string $newEmail,
        public ?string $verifyUrl = null
    ) {
        $baseUrl = UrlHelper::frontendUrl();
        $this->verifyUrl = $this->verifyUrl ?: ($baseUrl . '/verify-email-change?token=' . urlencode($this->token));
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
            ->subject('Verify Your New Email Address - ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('You recently requested to change the email address for your ' . config('app.name') . ' account to: ' . $this->newEmail)
            ->line('Please click the button below to verify this new email address and complete the update:')
            ->action('Verify Email Change', $this->verifyUrl)
            ->line('If you did not request this email change, please secure your account immediately.')
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
