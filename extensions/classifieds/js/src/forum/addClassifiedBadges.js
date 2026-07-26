import { extend } from 'flarum/common/extend';
import Discussion from 'flarum/common/models/Discussion';
import { formatPrice } from './utils';

export default function addClassifiedBadges() {
  extend(Discussion.prototype, 'badges', function (badges) {
    const price = this.attribute('price');
    const location = this.attribute('location');

    if (price !== null && price !== undefined) {
      badges.add(
        'classifiedPrice',
        <span className="ClassifiedBadge ClassifiedBadge--price">
          <i className="fas fa-tag" /> {formatPrice(price, this.attribute('currency'))}
        </span>,
        15
      );
    }

    if (location) {
      badges.add(
        'classifiedLocation',
        <span className="ClassifiedBadge ClassifiedBadge--location">
          <i className="fas fa-location-dot" /> {location}
        </span>,
        14
      );
    }
  });
}
