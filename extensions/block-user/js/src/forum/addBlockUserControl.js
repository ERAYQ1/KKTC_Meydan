import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import UserControls from 'flarum/forum/utils/UserControls';
import toggleBlock from './toggleBlock';

export default function addBlockUserControl() {
  extend(UserControls, 'userControls', function (items, user) {
    if (!app.session.user || app.session.user.id() === user.id()) {
      return;
    }

    const isBlocked = !!user.attribute('isBlocked');

    items.add(
      'block',
      <Button icon="fas fa-user-slash" onclick={() => toggleBlock(user)}>
        {app.translator.trans(isBlocked ? 'kktcmeydan-block-user.forum.unblock_button' : 'kktcmeydan-block-user.forum.block_button')}
      </Button>
    );
  });
}
