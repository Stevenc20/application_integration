<?php

namespace App\Notifications;

use App\Models\JobMaster;
use App\Models\RecoveryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ItemTidakTercapaiNotification extends Notification
{
    use Queueable;

    public JobMaster $job;
    public RecoveryItem $recoveryItem;

    public function __construct(JobMaster $job, RecoveryItem $recoveryItem)
    {
        $this->job = $job;
        $this->recoveryItem = $recoveryItem;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $planQty = $this->recoveryItem->plan_qty ?? 0;
        $actualQty = $this->recoveryItem->actual_qty ?? 0;
        $recoveryQty = $this->recoveryItem->recovery_qty ?? 0;

        return [
            'job_id'        => $this->job->id,
            'job_no'        => $this->job->job_number ?? '',
            'press_name'    => $this->job->line ?? '',
            'recovery_id'   => $this->recoveryItem->id,
            'plan_qty'      => $planQty,
            'actual_qty'    => $actualQty,
            'recovery_qty'  => $recoveryQty,
            'message'       => "Item {$this->job->job_number} tidak tercapai: {$actualQty}/{$planQty}. Sisa {$recoveryQty} pcs masuk recovery queue.",
        ];
    }
}
