import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';
import SettingsPage from 'flarum/forum/components/SettingsPage';
import FieldSet from 'flarum/common/components/FieldSet';
import addBlockUserControl from './addBlockUserControl';
import BlockedUsersList from './BlockedUsersList';

app.initializers.add('kktcmeydan-block-user', () => {
  User.prototype.blockedUsers = Model.hasMany('blockedUsers');

  addBlockUserControl();

  extend(SettingsPage.prototype, 'settingsItems', function (items) {
    if (!app.session.user) return;

    items.add(
      'blockedUsers',
      <FieldSet className="Settings-blockedUsers" label={app.translator.trans('kktcmeydan-block-user.forum.settings.title')}>
        <BlockedUsersList user={app.session.user} />
      </FieldSet>,
      40
    );
  });
});
