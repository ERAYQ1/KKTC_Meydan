import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import IndexPage from 'flarum/forum/components/IndexPage';
import DiscussionComposer from 'flarum/forum/components/DiscussionComposer';
import LogInModal from 'flarum/forum/components/LogInModal';
import NotificationsDropdown from 'flarum/forum/components/NotificationsDropdown';
import Link from 'flarum/common/components/Link';
import Button from 'flarum/common/components/Button';
import avatar from 'flarum/common/helpers/avatar';
import icon from 'flarum/common/helpers/icon';
import BottomSheet from './BottomSheet';
import discoverSheetBody from './DiscoverSheetBody';
import profileSheetBody from './ProfileSheetBody';

function trans(key) {
  return app.translator.trans(`kktcmeydan-mobile-ui.forum.nav.${key}`);
}

export default class BottomNav extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.discoverOpen = false;
    this.profileOpen = false;
  }

  isHome() {
    return app.current.matches(IndexPage) && !m.route.param('tags');
  }

  openDiscover() {
    if (!app.store.all('tags').length) {
      app.store.find('tags');
    }

    this.discoverOpen = true;
  }

  openProfile() {
    this.profileOpen = true;
  }

  startDiscussion() {
    if (app.session?.user) {
      app.composer.load(DiscussionComposer, { user: app.session.user });
      app.composer.show();
    } else {
      app.modal.show(LogInModal);
    }
  }

  view() {
    const user = app.session?.user;

    return (
      <div className="MobileBottomNav-wrapper">
        <nav className="MobileBottomNav">
          <Link href={app.route('index')} className={'MobileBottomNav-item' + (this.isHome() ? ' active' : '')}>
            {icon('fas fa-house')}
            <span>{trans('home')}</span>
          </Link>

          <button className="MobileBottomNav-item" onclick={() => this.openDiscover()}>
            {icon('fas fa-compass')}
            <span>{trans('discover')}</span>
          </button>

          <button
            className="MobileBottomNav-item MobileBottomNav-item--main"
            aria-label={trans('new_discussion')}
            onclick={() => this.startDiscussion()}
          >
            <span className="MobileBottomNav-mainButton">{icon('fas fa-plus')}</span>
          </button>

          <span className="MobileBottomNav-item MobileBottomNav-item--dropdown">
            {user ? (
              <NotificationsDropdown state={app.notifications} />
            ) : (
              <Button className="Button Button--icon" icon="fas fa-bell" onclick={() => app.modal.show(LogInModal)} />
            )}
            <span>{trans('notifications')}</span>
          </span>

          <button
            className="MobileBottomNav-item MobileBottomNav-item--profile"
            onclick={() => this.openProfile()}
          >
            {user ? avatar(user, { className: 'MobileBottomNav-avatar' }) : icon('fas fa-user')}
            <span>{trans('profile')}</span>
          </button>
        </nav>

        {this.discoverOpen && (
          <BottomSheet title={trans('discover')} onclose={() => (this.discoverOpen = false)}>
            {discoverSheetBody(() => (this.discoverOpen = false))}
          </BottomSheet>
        )}

        {this.profileOpen && (
          <BottomSheet title={trans('profile')} onclose={() => (this.profileOpen = false)}>
            {profileSheetBody(() => (this.profileOpen = false))}
          </BottomSheet>
        )}
      </div>
    );
  }
}
