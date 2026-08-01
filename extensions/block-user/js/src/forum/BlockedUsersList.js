import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import avatar from 'flarum/common/helpers/avatar';
import username from 'flarum/common/helpers/username';
import Link from 'flarum/common/components/Link';
import toggleBlock from './toggleBlock';

export default class BlockedUsersList extends Component {
  view() {
    const blockedUsers = (this.attrs.user.blockedUsers() || []).filter(Boolean);

    if (!blockedUsers.length) {
      return <p className="BlockedUsersList-empty">{app.translator.trans('kktcmeydan-block-user.forum.settings.empty')}</p>;
    }

    return (
      <ul className="BlockedUsersList">
        {blockedUsers.map((user) => (
          <li className="BlockedUsersList-item" key={user.id()}>
            <Link href={app.route.user(user)} className="BlockedUsersList-user">
              {avatar(user)} {username(user)}
            </Link>
            <Button className="Button Button--link" onclick={() => toggleBlock(user).then(() => m.redraw())}>
              {app.translator.trans('kktcmeydan-block-user.forum.unblock_button')}
            </Button>
          </li>
        ))}
      </ul>
    );
  }
}
