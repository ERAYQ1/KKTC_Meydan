import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import SettingsPage from 'flarum/forum/components/SettingsPage';
import UserCard from 'flarum/forum/components/UserCard';
import UserPage from 'flarum/forum/components/UserPage';
import FieldSet from 'flarum/common/components/FieldSet';
import Button from 'flarum/common/components/Button';
import LinkButton from 'flarum/common/components/LinkButton';
import icon from 'flarum/common/helpers/icon';
import BusinessReview from './BusinessReview';
import BusinessReviewsPage from './BusinessReviewsPage';
import StarRating from './StarRating';

const FIELDS = [
  { key: 'business_address', attribute: 'businessAddress', label: 'address_label', icon: 'fas fa-location-dot' },
  { key: 'business_phone', attribute: 'businessPhone', label: 'phone_label', icon: 'fas fa-phone' },
  { key: 'business_whatsapp', attribute: 'businessWhatsapp', label: 'whatsapp_label', icon: 'fab fa-whatsapp' },
  { key: 'business_hours', attribute: 'businessHours', label: 'hours_label', icon: 'far fa-clock' },
  { key: 'business_map_url', attribute: 'businessMapUrl', label: 'map_url_label', icon: 'fas fa-map-location-dot' },
  { key: 'business_photo_url', attribute: 'businessPhotoUrl', label: 'photo_url_label', icon: 'fas fa-image' },
];

app.initializers.add('kktcmeydan-business-profile', () => {
  extend(SettingsPage.prototype, 'settingsItems', function (items) {
    const user = this.user;

    if (!this.businessFields) {
      this.businessFields = {};
      FIELDS.forEach(({ key }) => {
        this.businessFields[key] = user.preferences()?.[key] || '';
      });
    }

    items.add(
      'business',
      <FieldSet className="Settings-business" label={app.translator.trans('kktcmeydan-business-profile.forum.settings.heading')}>
        {FIELDS.map(({ key, label }) => (
          <div className="Form-group">
            <label>{app.translator.trans(`kktcmeydan-business-profile.forum.settings.${label}`)}</label>
            <input
              className="FormControl"
              value={this.businessFields[key]}
              oninput={(e) => (this.businessFields[key] = e.target.value)}
            />
          </div>
        ))}
        <Button
          className="Button Button--primary"
          loading={this.businessSaveLoading}
          onclick={() => {
            this.businessSaveLoading = true;
            user.savePreferences({ ...this.businessFields }).then(() => {
              this.businessSaveLoading = false;
              m.redraw();
            });
          }}
        >
          {app.translator.trans('kktcmeydan-business-profile.forum.settings.save_button')}
        </Button>
      </FieldSet>,
      70
    );
  });

  extend(UserCard.prototype, 'infoItems', function (items) {
    const user = this.attrs.user;
    const isPopover = (this.attrs.className || '').includes('popover');

    if (user.attribute('businessPhotoUrl') && !isPopover) {
      items.add('businessPhoto', <img className="UserCard-businessPhoto" src={user.attribute('businessPhotoUrl')} />, 110);
    }

    if (user.attribute('isBusinessUser') && user.attribute('businessReviewCount') > 0) {
      items.add(
        'businessRating',
        <span className="UserCard-businessRating">
          <StarRating value={Math.round(user.attribute('businessAvgRating') || 0)} /> ({user.attribute('businessReviewCount')})
        </span>,
        60
      );
    }

    FIELDS.forEach(({ key, attribute, icon: fieldIcon }) => {
      if (key === 'business_photo_url') return;

      const value = user.attribute(attribute);

      if (!value) return;

      if (key === 'business_map_url') {
        items.add(
          key,
          <a className="UserCard-businessInfo" href={value} target="_blank" rel="noopener noreferrer nofollow">
            {icon(fieldIcon)} {app.translator.trans('kktcmeydan-business-profile.forum.map_link')}
          </a>,
          50
        );
        return;
      }

      items.add(
        key,
        <span className="UserCard-businessInfo">
          {icon(fieldIcon)} {value}
        </span>,
        50
      );
    });
  });

  extend(UserPage.prototype, 'navItems', function (items) {
    const user = this.user;

    if (user && user.attribute('isBusinessUser')) {
      items.add(
        'businessReviews',
        <LinkButton href={app.route('user.business-reviews', { username: user.slug() })} icon="fas fa-star">
          {app.translator.trans('kktcmeydan-business-profile.forum.reviews.heading')}{' '}
          <span className="Button-badge">{user.attribute('businessReviewCount') || 0}</span>
        </LinkButton>,
        80
      );
    }
  });

  app.store.models['business-reviews'] = BusinessReview;

  app.routes['user.business-reviews'] = {
    path: '/u/:username/isletme-yorumlari',
    component: BusinessReviewsPage,
  };
});
