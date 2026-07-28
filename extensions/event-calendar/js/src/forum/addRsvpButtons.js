import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import DiscussionHero from 'flarum/forum/components/DiscussionHero';
import Button from 'flarum/common/components/Button';

function trans(key, params) {
  return app.translator.trans(`kktcmeydan-event-calendar.forum.rsvp.${key}`, params);
}

function setRsvp(discussion, status) {
  return app.store
    .createRecord('event-rsvps')
    .save({ discussionId: discussion.id(), status })
    .then(() => {
      discussion.pushAttributes({ myRsvpStatus: status });
      m.redraw();
    });
}

export default function addRsvpButtons() {
  extend(DiscussionHero.prototype, 'items', function (items) {
    const discussion = this.attrs.discussion;

    if (!discussion.attribute('eventStartAt')) return;
    if (!app.session.user) return;

    const myStatus = discussion.attribute('myRsvpStatus');

    items.add(
      'rsvp',
      <div className="EventRsvp-buttons">
        <Button
          className={`Button ${myStatus === 'going' ? 'Button--primary' : ''}`}
          icon="fas fa-check"
          onclick={() => setRsvp(discussion, 'going')}
        >
          {trans('going_button')} ({discussion.attribute('rsvpGoingCount') || 0})
        </Button>{' '}
        <Button
          className={`Button ${myStatus === 'interested' ? 'Button--primary' : ''}`}
          icon="fas fa-star"
          onclick={() => setRsvp(discussion, 'interested')}
        >
          {trans('interested_button')} ({discussion.attribute('rsvpInterestedCount') || 0})
        </Button>
      </div>,
      5
    );
  });
}
