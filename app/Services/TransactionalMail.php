<?php

namespace App\Services;

use App\Jobs\SendTransactionalMail;
use App\Models\NotificationDelivery;

class TransactionalMail
{
    public function queue(int $userId, string $type, string $reference): void
    {
        $delivery = NotificationDelivery::firstOrCreate(['dedupe_key' => $type.':'.$reference.':'.$userId], ['user_id' => $userId, 'type' => $type, 'subject_reference' => $reference]);
        if ($delivery->status !== 'sent') {
            SendTransactionalMail::dispatch($delivery->id)->afterCommit();
        }
    }
}
