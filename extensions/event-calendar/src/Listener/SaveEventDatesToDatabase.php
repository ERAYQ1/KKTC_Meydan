<?php

namespace KktcMeydan\EventCalendar\Listener;

use Carbon\Carbon;
use Flarum\Discussion\Event\Saving;

class SaveEventDatesToDatabase
{
    public function handle(Saving $event)
    {
        $attributes = $event->data['attributes'] ?? [];
        $discussion = $event->discussion;

        $hasStart = array_key_exists('eventStartAt', $attributes);
        $hasEnd = array_key_exists('eventEndAt', $attributes);

        if (! $hasStart && ! $hasEnd) {
            return;
        }

        $start = $hasStart ? $this->parseDate($attributes['eventStartAt']) : $discussion->event_start_at;
        $end = $hasEnd ? $this->parseDate($attributes['eventEndAt']) : $discussion->event_end_at;

        // Invalid or inverted range (end before start): drop the end date rather
        // than reject the whole save, since the start date alone is still useful.
        if ($start && $end && $end->lt($start)) {
            $end = null;
        }

        if ($hasStart) {
            $discussion->event_start_at = $start;
        }

        if ($hasEnd) {
            $discussion->event_end_at = $end;
        }
    }

    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            $date = Carbon::parse($value);

            return ($date->year < 2000 || $date->year > 2100) ? null : $date;
        } catch (\Exception $e) {
            return null;
        }
    }
}
