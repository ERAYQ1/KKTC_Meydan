import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

function trans(key, params) {
  return app.translator.trans(`kktcmeydan-duty-pharmacy.forum.modal.${key}`, params);
}

const TAB_PHARMACIES = 'pharmacies';
const TAB_EMERGENCY = 'emergency';

export default class DutyPharmacyModal extends Modal {
  static isDismissible = true;

  oninit(vnode) {
    super.oninit(vnode);

    this.activeTab = TAB_PHARMACIES;
    this.activeDistrict = null;
    this.loading = true;
    this.error = false;
    this.data = null;

    this.loadData();
  }

  className() {
    return 'DutyPharmacyModal Modal--large';
  }

  title() {
    return trans('title');
  }

  content() {
    if (this.loading) {
      return (
        <div className="Modal-body DutyPharmacyModal-loading">
          <LoadingIndicator />
        </div>
      );
    }

    if (this.error || !this.data) {
      return <div className="Modal-body DutyPharmacyModal-error">{trans('error')}</div>;
    }

    return (
      <div className="Modal-body DutyPharmacyModal-body">
        {this.data.source === 'fallback' && <div className="DutyPharmacyModal-notice">{trans('fallback_notice')}</div>}

        <ul className="DutyPharmacyModal-tabs">
          <li className={this.activeTab === TAB_PHARMACIES ? 'active' : ''}>
            <Button className="Button" onclick={() => (this.activeTab = TAB_PHARMACIES)}>
              💊 {trans('tabs.pharmacies')}
            </Button>
          </li>
          <li className={this.activeTab === TAB_EMERGENCY ? 'active' : ''}>
            <Button className="Button" onclick={() => (this.activeTab = TAB_EMERGENCY)}>
              📞 {trans('tabs.emergency')}
            </Button>
          </li>
        </ul>

        {this.activeTab === TAB_PHARMACIES ? this.renderPharmaciesTab() : this.renderEmergencyTab()}
      </div>
    );
  }

  renderPharmaciesTab() {
    const districts = this.data.districts;
    const activeSlug = this.activeDistrict || (districts[0] && districts[0].slug);
    const activeDistrict = districts.find((district) => district.slug === activeSlug);

    return (
      <div className="DutyPharmacyModal-pharmacies">
        <ul className="DutyPharmacyModal-districtTabs">
          {districts.map((district) => (
            <li key={district.slug}>
              <Button
                className={'Button Button--link' + (district.slug === activeSlug ? ' active' : '')}
                onclick={() => (this.activeDistrict = district.slug)}
              >
                {district.name}
              </Button>
            </li>
          ))}
        </ul>

        {activeDistrict && activeDistrict.pharmacies.length === 0 && <p className="DutyPharmacyModal-empty">{trans('no_pharmacies')}</p>}

        <div className="DutyPharmacyModal-cards">
          {activeDistrict && activeDistrict.pharmacies.map((pharmacy, index) => this.renderPharmacyCard(pharmacy, index))}
        </div>
      </div>
    );
  }

  renderPharmacyCard(pharmacy, index) {
    return (
      <div className="DutyPharmacyCard" key={index}>
        <h4 className="DutyPharmacyCard-name">{pharmacy.name}</h4>
        {pharmacy.hours && (
          <p className="DutyPharmacyCard-hours">
            {trans('hours_label')} {pharmacy.hours}
          </p>
        )}
        <p className="DutyPharmacyCard-address">{pharmacy.address}</p>
        <div className="DutyPharmacyCard-actions">
          {pharmacy.phone && (
            <a className="Button Button--primary" href={pharmacy.phone}>
              {trans('call_button')}
            </a>
          )}
          <a className="Button" href={pharmacy.mapUrl} target="_blank" rel="noopener noreferrer">
            {trans('map_button')}
          </a>
        </div>
      </div>
    );
  }

  renderEmergencyTab() {
    return (
      <div className="DutyPharmacyModal-emergencyGrid">
        {this.data.emergencyNumbers.map((entry) => (
          <a className="EmergencyCard" href={entry.phone} key={entry.number}>
            <span className="EmergencyCard-label">{entry.label}</span>
            <span className="EmergencyCard-number">{entry.number}</span>
          </a>
        ))}
      </div>
    );
  }

  loadData() {
    app
      .request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/duty-pharmacies',
      })
      .then((response) => {
        this.data = response;
        this.loading = false;
        m.redraw();
      })
      .catch(() => {
        this.error = true;
        this.loading = false;
        m.redraw();
      });
  }
}
