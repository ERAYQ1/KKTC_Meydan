import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import icon from 'flarum/common/helpers/icon';

function trans(key, params) {
  return app.translator.trans(`kktcmeydan-social-share.forum.modal.${key}`, params);
}

export default class ShareModal extends Modal {
  static isDismissible = true;

  oninit(vnode) {
    super.oninit(vnode);

    this.discussion = this.attrs.discussion;
    this.url = window.location.origin + app.route.discussion(this.discussion);
  }

  className() {
    return 'ShareModal Modal--small';
  }

  title() {
    return trans('title');
  }

  content() {
    const title = this.discussion.title();
    const url = this.url;

    const links = [
      {
        key: 'whatsapp',
        icon: 'fab fa-whatsapp',
        href: `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`,
      },
      {
        key: 'x',
        icon: 'fab fa-x-twitter',
        href: `https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`,
      },
      {
        key: 'facebook',
        icon: 'fab fa-facebook',
        href: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
      },
    ];

    return (
      <div className="Modal-body">
        <ul className="ShareModal-links">
          {links.map((link) => (
            <li key={link.key}>
              <a href={link.href} target="_blank" rel="noopener noreferrer" className="Button Button--block ShareModal-link">
                {icon(link.icon)} {trans(link.key)}
              </a>
            </li>
          ))}
        </ul>

        <Button className="Button Button--block ShareModal-copyButton" icon="fas fa-link" onclick={() => this.copyLink()}>
          {trans('copy_link')}
        </Button>
      </div>
    );
  }

  copyLink() {
    navigator.clipboard.writeText(this.url).then(() => {
      app.alerts.show({ type: 'success' }, trans('link_copied'));
      this.hide();
    });
  }
}
