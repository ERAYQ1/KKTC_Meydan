import app from 'flarum/forum/app';
import Link from 'flarum/common/components/Link';
import LogInModal from 'flarum/forum/components/LogInModal';
import SignUpModal from 'flarum/forum/components/SignUpModal';
import icon from 'flarum/common/helpers/icon';

function trans(key) {
  return app.translator.trans(`kktcmeydan-mobile-ui.forum.nav.${key}`);
}

export default function profileSheetBody(onNavigate) {
  const user = app.session.user;
  const adminUrl = app.forum.attribute('adminUrl');

  if (!user) {
    return (
      <ul className="MobileDiscoverList">
        <li>
          <a
            onclick={() => {
              onNavigate();
              app.modal.show(LogInModal);
            }}
          >
            <span className="MobileDiscoverList-icon">{icon('fas fa-right-to-bracket')}</span>
            <span className="MobileDiscoverList-name">{trans('log_in')}</span>
          </a>
        </li>

        <li>
          <a
            onclick={() => {
              onNavigate();
              app.modal.show(SignUpModal);
            }}
          >
            <span className="MobileDiscoverList-icon">{icon('fas fa-user-plus')}</span>
            <span className="MobileDiscoverList-name">{trans('sign_up')}</span>
          </a>
        </li>
      </ul>
    );
  }

  return (
    <ul className="MobileDiscoverList">
      <li>
        <Link href={app.route.user(user)} onclick={() => onNavigate()}>
          <span className="MobileDiscoverList-icon">{icon('fas fa-user')}</span>
          <span className="MobileDiscoverList-name">{trans('profile')}</span>
        </Link>
      </li>

      <li>
        <Link href={app.route('settings')} onclick={() => onNavigate()}>
          <span className="MobileDiscoverList-icon">{icon('fas fa-gear')}</span>
          <span className="MobileDiscoverList-name">{trans('settings')}</span>
        </Link>
      </li>

      {adminUrl && (
        <li>
          <a href={adminUrl} target="_blank" rel="noopener noreferrer" onclick={() => onNavigate()}>
            <span className="MobileDiscoverList-icon">{icon('fas fa-wrench')}</span>
            <span className="MobileDiscoverList-name">{trans('admin')}</span>
          </a>
        </li>
      )}

      <li>
        <a
          onclick={() => {
            onNavigate();
            app.session.logout();
          }}
        >
          <span className="MobileDiscoverList-icon">{icon('fas fa-right-from-bracket')}</span>
          <span className="MobileDiscoverList-name">{trans('log_out')}</span>
        </a>
      </li>
    </ul>
  );
}
