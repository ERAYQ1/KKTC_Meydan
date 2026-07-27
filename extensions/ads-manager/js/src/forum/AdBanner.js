import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import { getAds, matchesTag } from './AdsStore';

export default class AdBanner extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.ad = null;
    this.loaded = false;
    this.impressionSent = false;
    this.observer = null;

    const tag = m.route.param('tags') || null;

    getAds()
      .then((ads) => {
        const eligible = ads.filter((ad) => matchesTag(ad, tag));
        this.ad = eligible.length ? eligible[Math.floor(Math.random() * eligible.length)] : null;
        this.loaded = true;

        m.redraw();
      })
      .catch(() => {
        this.loaded = true;
        m.redraw();
      });
  }

  oncreate(vnode) {
    super.oncreate(vnode);
    this.watchForImpression();
  }

  onupdate(vnode) {
    super.onupdate(vnode);
    this.watchForImpression();
  }

  onremove(vnode) {
    this.disconnectObserver();
    super.onremove(vnode);
  }

  // An impression is only meaningful once the banner is actually visible -
  // firing it as soon as the ad loads (the old behaviour) counted banners
  // that were still off-screen below the fold, or never scrolled to at all.
  watchForImpression() {
    if (this.observer || this.impressionSent || !this.ad || !this.element) {
      return;
    }

    this.observer = new IntersectionObserver(
      (entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
          this.sendImpression();
        }
      },
      { threshold: 0.5 }
    );

    this.observer.observe(this.element);
  }

  disconnectObserver() {
    if (this.observer) {
      this.observer.disconnect();
      this.observer = null;
    }
  }

  sendImpression() {
    if (this.impressionSent || !this.ad) {
      return;
    }

    this.impressionSent = true;
    this.disconnectObserver();

    app.request({
      method: 'POST',
      url: `${app.forum.attribute('apiUrl')}/ads/${this.ad.id()}/impression`,
    });
  }

  recordClick() {
    app.request({
      method: 'POST',
      url: `${app.forum.attribute('apiUrl')}/ads/${this.ad.id()}/click`,
    });
  }

  view() {
    if (!this.ad) return null;

    return (
      <a
        className="AdBanner"
        href={this.ad.attribute('targetUrl')}
        target="_blank"
        rel="noopener noreferrer sponsored"
        onclick={() => this.recordClick()}
      >
        <img className="AdBanner-image" src={this.ad.attribute('imageUrl')} alt={this.ad.attribute('title')} />
        <div className="AdBanner-body">
          <div className="AdBanner-badge">{app.translator.trans('kktcmeydan-ads-manager.forum.sponsored_label')}</div>
          <div className="AdBanner-title">{this.ad.attribute('title')}</div>
        </div>
      </a>
    );
  }
}
