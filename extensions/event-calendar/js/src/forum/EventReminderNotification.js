import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';

export default class EventReminderNotification extends Notification {
  icon() {
    return 'fas fa-calendar-days';
  }

  href() {
    return app.route.discussion(this.attrs.notification.subject());
  }

  content() {
    const discussion = this.attrs.notification.subject();

    return app.translator.trans('kktcmeydan-event-calendar.forum.notifications.kktcmeydanEventReminder', {
      discussion: discussion ? discussion.title() : '',
    });
  }
}
