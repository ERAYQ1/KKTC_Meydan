import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import SettingsPage from 'flarum/forum/components/SettingsPage';
import UserCard from 'flarum/forum/components/UserCard';
import FieldSet from 'flarum/common/components/FieldSet';
import Button from 'flarum/common/components/Button';
import icon from 'flarum/common/helpers/icon';

const FIELDS = [
  { key: 'business_address', attribute: 'businessAddress', label: 'address_label', icon: 'fas fa-location-dot' },
  { key: 'business_phone', attribute: 'businessPhone', label: 'phone_label', icon: 'fas fa-phone' },
  { key: 'business_whatsapp', attribute: 'businessWhatsapp', label: 'whatsapp_label', icon: 'fab fa-whatsapp' },
  { key: 'business_hours', attribute: 'businessHours', label: 'hours_label', icon: 'far fa-clock' },
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

    FIELDS.forEach(({ key, attribute, icon: fieldIcon }) => {
      const value = user.attribute(attribute);

      if (value) {
        items.add(
          key,
          <span className="UserCard-businessInfo">
            {icon(fieldIcon)} {value}
          </span>,
          50
        );
      }
    });
  });
});
