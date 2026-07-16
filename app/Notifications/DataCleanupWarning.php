<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DataCleanupWarning extends Notification
{
    use Queueable;

    public function __construct(
        public int $retentionMonths,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cutoff = now()->subMonths($this->retentionMonths)->startOfDay()->format('d F Y');

        return (new MailMessage)
            ->subject('⚠️ Peringatan: Data Produksi Akan Dibersihkan')
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line("Data produksi yang lebih dari **{$this->retentionMonths} bulan** (sebelum {$cutoff}) akan dihapus secara berkala.")
            ->line('Jika ada data yang perlu dipertahankan, segera lakukan backup/export.')
            ->action('Lihat Production Analytics', url('/analytics/production'))
            ->line('Terima kasih.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Data Cleanup Warning',
            'message' => "Data produksi > {$this->retentionMonths} bulan akan dihapus secara berkala.",
            'retention_months' => $this->retentionMonths,
        ];
    }
}
