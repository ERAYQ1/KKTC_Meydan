import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';

export default class AdBanner extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.ad = null;
    this.loaded = false;
    this.impressionSent = false;

    const tag = m.route.param('tags') || null;

    app.store
      .find('ads', { filter: tag ? { tag } : {} })
      .then((ads) => {
        this.ad = ads.length ? ads[Math.floor(Math.random() * ads.length)] : null;
        this.loaded = true;

        if (this.ad && !this.impressionSent) {
          this.impressionSent = true;

          app.request({
            method: 'POST',
            url: `${app.forum.attribute('apiUrl')}/ads/${this.ad.id()}/impression`,
          });
        }

        m.redraw();
      })
      .catch(() => {
        this.loaded = true;
        m.redraw();
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
