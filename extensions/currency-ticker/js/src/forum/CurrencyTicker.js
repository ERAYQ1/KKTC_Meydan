import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';

export default class CurrencyTicker extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.rates = null;
    this.loading = true;
    this.failed = false;

    app
      .request({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/currency-rates`,
      })
      .then((response) => {
        this.rates = response.rates;
        this.loading = false;
        m.redraw();
      })
      .catch(() => {
        this.failed = true;
        this.loading = false;
        m.redraw();
      });
  }

  view() {
    if (this.loading) {
      return (
        <div className="CurrencyTicker CurrencyTicker--loading">
          <span className="CurrencyTicker-item">{app.translator.trans('kktcmeydan-currency-ticker.forum.loading')}</span>
        </div>
      );
    }

    if (this.failed || !this.rates) {
      return null;
    }

    return (
      <div className="CurrencyTicker">
        <div className="CurrencyTicker-track">
          <span className="CurrencyTicker-item">
            <span className="CurrencyTicker-icon">💱</span> £ 1 GBP = {this.rates.GBP.toFixed(2)} TL
          </span>
          <span className="CurrencyTicker-item">€ 1 EUR = {this.rates.EUR.toFixed(2)} TL</span>
          <span className="CurrencyTicker-item">$ 1 USD = {this.rates.USD.toFixed(2)} TL</span>
        </div>
      </div>
    );
  }
}
