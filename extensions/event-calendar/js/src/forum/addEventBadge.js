import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Discussion from 'flarum/common/models/Discussion';
import { formatEventDate } from './utils';

export default function addEventBadge() {
  extend(Discussion.prototype, 'badges', function (badges) {
    const start = this.attribute('eventStartAt');

    if (!start) return;

    const end = this.attribute('eventEndAt');
    const range = end ? `${formatEventDate(start)} – ${formatEventDate(end)}` : formatEventDate(start);

    badges.add(
      'eventDate',
      <span className="EventDateBadge" title={app.translator.trans('kktcmeydan-event-calendar.forum.badge.tooltip', { range })}>
        <i className="fas fa-calendar-days" /> {range}
      </span>,
      16
    );
  });
}
