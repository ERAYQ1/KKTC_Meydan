import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import IndexPage from 'flarum/forum/components/IndexPage';
import LinkButton from 'flarum/common/components/LinkButton';
import NotificationGrid from 'flarum/forum/components/NotificationGrid';
import addEventComposerFields from './addEventComposerFields';
import addEventBadge from './addEventBadge';
import addRsvpButtons from './addRsvpButtons';
import CalendarPage from './CalendarPage';
import EventRsvp from './EventRsvp';
import EventReminderNotification from './EventReminderNotification';

app.initializers.add('kktcmeydan-event-calendar', () => {
  addEventComposerFields();
  addEventBadge();
  addRsvpButtons();

  app.store.models['event-rsvps'] = EventRsvp;
  app.notificationComponents.kktcmeydanEventReminder = EventReminderNotification;

  app.routes['events.calendar'] = {
    path: '/etkinlikler',
    component: CalendarPage,
  };

  extend(IndexPage.prototype, 'navItems', function (items) {
    items.add(
      'eventCalendar',
      <LinkButton href={app.route('events.calendar')} icon="fas fa-calendar-days">
        {app.translator.trans('kktcmeydan-event-calendar.forum.calendar.nav_link')}
      </LinkButton>,
      -10
    );
  });

  extend(NotificationGrid.prototype, 'notificationTypes', function (items) {
    items.add('kktcmeydanEventReminder', {
      name: 'kktcmeydanEventReminder',
      icon: 'fas fa-calendar-days',
      label: app.translator.trans('kktcmeydan-event-calendar.forum.calendar.nav_link'),
    });
  });
});
