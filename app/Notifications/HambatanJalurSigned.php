<?php

namespace App\Notifications;

use App\Models\HambatanJalur;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class HambatanJalurSigned extends Notification
{
    use Queueable;

    public HambatanJalur $hambatan;

    public function __construct(HambatanJalur $hambatan)
    {
        $this->hambatan = $hambatan;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'hambatan_id' => $this->hambatan->id,
            'line_name' => $this->hambatan->line_name,
            'jenis_hambatan' => $this->hambatan->jenis_hambatan,
            'pic_hambatan' => $this->hambatan->pic_hambatan,
            'mesin' => $this->hambatan->mesin,
            'message' => 'Hambatan ' . $this->hambatan->line_name . ' (' . $this->hambatan->jenis_hambatan . ') telah ditandatangani PIC dan membutuhkan tanda tangan Anda.',
        ];
    }
}
