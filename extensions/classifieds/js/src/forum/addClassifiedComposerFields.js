import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import DiscussionComposer from 'flarum/forum/components/DiscussionComposer';
import Stream from 'flarum/common/utils/Stream';
import { CLASSIFIED_TRIGGER_SLUGS, CURRENCIES, CLASSIFIED_TYPES } from './constants';

function trans(key) {
  return app.translator.trans(`kktcmeydan-classifieds.forum.composer.${key}`);
}

export default function addClassifiedComposerFields() {
  extend(DiscussionComposer.prototype, 'oninit', function () {
    const fields = this.composer.fields;

    fields.price = fields.price || Stream('');
    fields.currency = fields.currency || Stream('TRY');
    fields.location = fields.location || Stream('');
    fields.contactPhone = fields.contactPhone || Stream('');
    fields.classifiedType = fields.classifiedType || Stream('');
  });

  extend(DiscussionComposer.prototype, 'headerItems', function (items) {
    const tags = this.composer.fields.tags || [];
    const showFields = tags.some((tag) => CLASSIFIED_TRIGGER_SLUGS.includes(tag.slug()));

    if (!showFields) return;

    const fields = this.composer.fields;

    items.add(
      'classifiedFields',
      <div className="ClassifiedComposerFields">
        <div className="ClassifiedComposerFields-row">
          <input
            className="FormControl ClassifiedComposerFields-price"
            type="number"
            min="0"
            step="0.01"
            placeholder={trans('price_label')}
            bidi={fields.price}
          />
          <select className="FormControl ClassifiedComposerFields-currency" bidi={fields.currency}>
            {CURRENCIES.map((currency) => (
              <option value={currency}>{currency}</option>
            ))}
          </select>
          <input
            className="FormControl ClassifiedComposerFields-location"
            type="text"
            placeholder={trans('location_label')}
            bidi={fields.location}
          />
        </div>
        <div className="ClassifiedComposerFields-row">
          <input
            className="FormControl ClassifiedComposerFields-phone"
            type="text"
            placeholder={trans('contact_phone_label')}
            bidi={fields.contactPhone}
          />
          <select className="FormControl ClassifiedComposerFields-type" bidi={fields.classifiedType}>
            <option value="">{trans('classified_type_none')}</option>
            {CLASSIFIED_TYPES.map((type) => (
              <option value={type}>{trans('classified_type_' + type)}</option>
            ))}
          </select>
        </div>
      </div>,
      5
    );
  });

  extend(DiscussionComposer.prototype, 'data', function (data) {
    const tags = this.composer.fields.tags || [];
    const showFields = tags.some((tag) => CLASSIFIED_TRIGGER_SLUGS.includes(tag.slug()));

    if (!showFields) return;

    const fields = this.composer.fields;

    data.price = fields.price() === '' ? null : fields.price();
    data.currency = fields.currency();
    data.location = fields.location();
    data.contactPhone = fields.contactPhone();
    data.classifiedType = fields.classifiedType() || null;
  });
}
