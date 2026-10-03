<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * In-app (and optional e-mail) alert for the ministry team:
 * new connect cards, follow-up assignments, join requests, volunteer applications, prayer requests.
 */
class TeamAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $category,
        public string $title,
        public string $message,
        public ?string $url = null,
        public bool $sendMail = false,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        // Accounts may sign in with a plain username (e.g. "admin"); only real addresses get e-mail.
        $hasMailbox = filter_var($notifiable->email ?? null, FILTER_VALIDATE_EMAIL) !== false;

        return $this->sendMail && $hasMailbox ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title.' — Every Nation Bekasi')
            ->greeting('Hi '.($notifiable->nickname ?: $notifiable->name).',')
            ->line($this->message)
            ->when($this->url, fn (MailMessage $mail) => $mail->action('Open', $this->url))
            ->line('Honor God. Make Disciples.');
    }

    /** @return array<string, string|null> */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => $this->category,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
