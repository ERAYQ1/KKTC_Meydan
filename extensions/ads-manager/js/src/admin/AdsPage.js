import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import EditAdModal from './EditAdModal';

function trans(key, params) {
  return app.translator.trans(`kktcmeydan-ads-manager.admin.page.${key}`, params);
}

export default class AdsPage extends ExtensionPage {
  oninit(vnode) {
    super.oninit(vnode);

    this.loading = true;
    this.ads = [];

    this.loadAds();
  }

  loadAds() {
    this.loading = true;

    app.store
      .find('ads', { filter: { all: 1 } })
      .then((ads) => {
        this.ads = ads;
        this.loading = false;
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }

  content() {
    return (
      <div className="AdsPage container">
        <div className="AdsPage-toolbar">
          <Button className="Button Button--primary" icon="fas fa-plus" onclick={() => this.openModal(null)}>
            {trans('add_button')}
          </Button>
        </div>

        {this.loading ? (
          <LoadingIndicator />
        ) : this.ads.length ? (
          this.adsTable()
        ) : (
          <p className="helpText">{trans('empty')}</p>
        )}
      </div>
    );
  }

  adsTable() {
    return (
      <table className="AdsPage-table">
        <thead>
          <tr>
            <th></th>
            <th>{trans('table.title')}</th>
            <th>{trans('table.target')}</th>
            <th>{trans('table.stats')}</th>
            <th>{trans('table.status')}</th>
            <th>{trans('table.actions')}</th>
          </tr>
        </thead>
        <tbody>{this.ads.map((ad) => this.adRow(ad))}</tbody>
      </table>
    );
  }

  adRow(ad) {
    const target =
      ad.attribute('targetCategorySlug') ||
      ad.attribute('targetDistrictSlug') ||
      ad.attribute('targetUniversitySlug') ||
      '—';

    return (
      <tr key={ad.id()}>
        <td>
          <img src={ad.attribute('imageUrl')} alt="" />
        </td>
        <td>{ad.attribute('title')}</td>
        <td>{target}</td>
        <td>
          {ad.attribute('impressionsCount')} / {ad.attribute('clicksCount')}
        </td>
        <td>
          <span className={ad.attribute('isActive') ? 'AdsPage-status--active' : 'AdsPage-status--inactive'}>
            {trans(`status.${ad.attribute('isActive') ? 'active' : 'inactive'}`)}
          </span>
        </td>
        <td>
          <Button className="Button Button--link" icon="fas fa-pencil-alt" onclick={() => this.openModal(ad)}>
            {trans('actions.edit')}
          </Button>{' '}
          <Button className="Button Button--link" icon="fas fa-trash" onclick={() => this.deleteAd(ad)}>
            {trans('actions.delete')}
          </Button>
        </td>
      </tr>
    );
  }

  openModal(ad) {
    app.modal.show(EditAdModal, { ad, onsaved: () => this.loadAds() });
  }

  deleteAd(ad) {
    if (!confirm(trans('delete_confirm'))) return;

    ad.delete().then(() => this.loadAds());
  }
}
