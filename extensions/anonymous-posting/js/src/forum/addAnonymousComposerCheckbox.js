import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import DiscussionComposer from 'flarum/forum/components/DiscussionComposer';
import ReplyComposer from 'flarum/forum/components/ReplyComposer';
import Stream from 'flarum/common/utils/Stream';
import { ANONYMOUS_TAG_SLUG } from './constants';

function trans(key) {
  return app.translator.trans(`kktcmeydan-anonymous-posting.forum.${key}`);
}

function checkboxItem(fields) {
  return (
    <div className="AnonymousComposerCheckbox">
      <label className="checkbox">
        <input
          type="checkbox"
          checked={fields.isAnonymous()}
          onchange={(e) => fields.isAnonymous(e.target.checked)}
        />
        {trans('composer.checkbox_label')}
      </label>
    </div>
  );
}

export default function addAnonymousComposerCheckbox() {
  // New discussion: gated on the tags chosen in the composer's tag picker.
  extend(DiscussionComposer.prototype, 'oninit', function () {
    const fields = this.composer.fields;
    fields.isAnonymous = fields.isAnonymous || Stream(false);
  });

  extend(DiscussionComposer.prototype, 'headerItems', function (items) {
    const tags = this.composer.fields.tags || [];
    const allowed = tags.some((tag) => tag.slug() === ANONYMOUS_TAG_SLUG);

    if (!allowed) return;

    items.add('anonymousCheckbox', checkboxItem(this.composer.fields), 3);
  });

  extend(DiscussionComposer.prototype, 'data', function (data) {
    const tags = this.composer.fields.tags || [];
    const allowed = tags.some((tag) => tag.slug() === ANONYMOUS_TAG_SLUG);

    data.isAnonymous = allowed && this.composer.fields.isAnonymous();
  });

  // Reply: gated on the tags already attached to the discussion being replied to.
  extend(ReplyComposer.prototype, 'oninit', function () {
    const fields = this.composer.fields;
    fields.isAnonymous = fields.isAnonymous || Stream(false);
  });

  extend(ReplyComposer.prototype, 'headerItems', function (items) {
    const discussion = this.attrs.discussion;
    const tags = (typeof discussion.tags === 'function' && discussion.tags()) || [];
    const allowed = tags.some((tag) => tag.slug() === ANONYMOUS_TAG_SLUG);

    if (!allowed) return;

    items.add('anonymousCheckbox', checkboxItem(this.composer.fields), 3);
  });

  extend(ReplyComposer.prototype, 'data', function (data) {
    const discussion = this.attrs.discussion;
    const tags = (typeof discussion.tags === 'function' && discussion.tags()) || [];
    const allowed = tags.some((tag) => tag.slug() === ANONYMOUS_TAG_SLUG);

    data.isAnonymous = allowed && this.composer.fields.isAnonymous();
  });
}
