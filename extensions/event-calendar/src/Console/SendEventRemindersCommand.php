<?php

namespace KktcMeydan\EventCalendar\Console;

use Carbon\Carbon;
use Flarum\Console\AbstractCommand;
use Flarum\Discussion\Discussion;
use Flarum\Notification\NotificationSyncer;
use KktcMeydan\EventCalendar\EventRsvp;
use KktcMeydan\EventCalendar\Notification\EventReminderBlueprint;

/**
 * Etkinlik baslamadan ~24 saat once RSVP'li kullanicilara hatirlatma
 * bildirimi gonderir. Host `flarum schedule:run` cron'una bagli (bkz.
 * extend.php'deki Extend\Console::schedule ve docker/crontab).
 */
class SendEventRemindersCommand extends AbstractCommand
{
    const REMINDER_WINDOW_HOURS = 24;

    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    public function __construct(NotificationSyncer $notifications)
    {
        parent::__construct();

        $this->notifications = $notifications;
    }

    protected function configure()
    {
        $this
            ->setName('kktcmeydan:send-event-reminders')
            ->setDescription('Yaklasan etkinlikler icin RSVPli kullanicilara hatirlatma bildirimi gonderir.');
    }

    protected function fire()
    {
        $now = Carbon::now();
        $windowEnd = (clone $now)->addHours(self::REMINDER_WINDOW_HOURS);

        $discussions = Discussion::query()
            ->whereNotNull('event_start_at')
            ->where('event_start_at', '>', $now)
            ->where('event_start_at', '<=', $windowEnd)
            ->get();

        $sent = 0;

        foreach ($discussions as $discussion) {
            $pendingRsvps = EventRsvp::where('discussion_id', $discussion->id)
                ->whereNull('reminded_at')
                ->get();

            if ($pendingRsvps->isEmpty()) {
                continue;
            }

            $users = $pendingRsvps->map(function (EventRsvp $rsvp) {
                return $rsvp->user;
            })->filter()->all();

            if (empty($users)) {
                continue;
            }

            $this->notifications->sync(new EventReminderBlueprint($discussion), $users);

            EventRsvp::whereIn('id', $pendingRsvps->pluck('id'))->update(['reminded_at' => $now]);

            $sent += count($users);
        }

        $this->output->writeln("$sent hatirlatma bildirimi gonderildi.");
    }
}
