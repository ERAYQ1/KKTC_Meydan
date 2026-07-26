<?php

namespace KktcMeydan\ReportStatus\Listener;

use Flarum\Discussion\Event\Saving;

class SaveReportStatusToDatabase
{
    const VALID_STATUSES = ['bildirildi', 'inceleniyor', 'yetkiliye-iletildi', 'cozuldu'];

    public function handle(Saving $event)
    {
        if (array_key_exists('reportStatus', $event->data['attributes'] ?? [])) {
            $status = $event->data['attributes']['reportStatus'];

            if ($status !== null && ! in_array($status, self::VALID_STATUSES, true)) {
                return;
            }

            $event->actor->assertCan('editReportStatus', $event->discussion);

            $event->discussion->report_status = $status;
        }
    }
}
