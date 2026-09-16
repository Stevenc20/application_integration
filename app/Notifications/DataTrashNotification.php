<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DataTrashNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $action,
        public int $count,
        public string $detail = '',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line($this->buildMessage())
            ->action('Lihat Recycle Bin', url('/super-admin/recycle-bin'))
            ->line('Terima kasih.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => match($this->action) {
                'trashed' => 'Data Produksi Masuk Recycle Bin',
                'expired' => 'Data Recycle Bin Akan Dihapus',
                'deleted' => 'Data Recycle Bin Dihapus Permanen',
                default => 'Info Recycle Bin',
            },
            'message' => $this->buildMessage(),
            'action' => $this->action,
            'count' => $this->count,
        ];
    }

    private function buildMessage(): string
    {
        return match($this->action) {
            'trashed' => "{$this->count} data produksi lama telah dipindahkan ke Recycle Bin. {$this->detail}",
            'expired' => "{$this->count} data di Recycle Bin akan segera kedaluwarsa dan dihapus permanen. {$this->detail}",
            'deleted' => "{$this->count} data expired telah dihapus permanen dari Recycle Bin. {$this->detail}",
            default => $this->detail ?: "Info Recycle Bin: {$this->count} data.",
        };
    }
}
