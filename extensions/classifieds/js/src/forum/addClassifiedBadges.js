import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Discussion from 'flarum/common/models/Discussion';
import { formatPrice } from './utils';

export default function addClassifiedBadges() {
  extend(Discussion.prototype, 'badges', function (badges) {
    const price = this.attribute('price');
    const location = this.attribute('location');

    if (price !== null && price !== undefined) {
      const formatted = formatPrice(price, this.attribute('currency'));

      badges.add(
        'classifiedPrice',
        <span
          className="ClassifiedBadge ClassifiedBadge--price"
          title={app.translator.trans('kktcmeydan-classifieds.forum.badge.price_tooltip', { price: formatted })}
        >
          <i className="fas fa-tag" /> {formatted}
        </span>,
        15
      );
    }

    if (location) {
      badges.add(
        'classifiedLocation',
        <span
          className="ClassifiedBadge ClassifiedBadge--location"
          title={app.translator.trans('kktcmeydan-classifieds.forum.badge.location_tooltip', { location })}
        >
          <i className="fas fa-location-dot" /> {location}
        </span>,
        14
      );
    }
  });
}
