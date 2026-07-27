import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import DiscussionComposer from 'flarum/forum/components/DiscussionComposer';
import Stream from 'flarum/common/utils/Stream';
import { EVENT_TRIGGER_SLUGS } from './constants';

function trans(key) {
  return app.translator.trans(`kktcmeydan-event-calendar.forum.composer.${key}`);
}

function showsEventFields(composer) {
  const tags = composer.fields.tags || [];

  return tags.some((tag) => EVENT_TRIGGER_SLUGS.includes(tag.slug()));
}

export default function addEventComposerFields() {
  extend(DiscussionComposer.prototype, 'oninit', function () {
    const fields = this.composer.fields;

    fields.eventStartAt = fields.eventStartAt || Stream('');
    fields.eventEndAt = fields.eventEndAt || Stream('');
  });

  extend(DiscussionComposer.prototype, 'headerItems', function (items) {
    if (!showsEventFields(this.composer)) return;

    const fields = this.composer.fields;

    items.add(
      'eventDateFields',
      <div className="EventComposerFields">
        <h3 className="EventComposerFields-heading">{trans('heading')}</h3>
        <div className="EventComposerFields-field">
          <label>{trans('start_label')}</label>
          <input className="FormControl" type="datetime-local" bidi={fields.eventStartAt} />
        </div>
        <div className="EventComposerFields-field">
          <label>{trans('end_label')}</label>
          <input className="FormControl" type="datetime-local" bidi={fields.eventEndAt} />
        </div>
      </div>,
      4
    );
  });

  extend(DiscussionComposer.prototype, 'data', function (data) {
    if (!showsEventFields(this.composer)) return;

    const fields = this.composer.fields;

    data.eventStartAt = fields.eventStartAt() || null;
    data.eventEndAt = fields.eventEndAt() || null;
  });
}
