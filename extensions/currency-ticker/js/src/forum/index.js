import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import IndexPage from 'flarum/forum/components/IndexPage';
import CurrencyTicker from './CurrencyTicker';

app.initializers.add('kktcmeydan-currency-ticker', () => {
  extend(IndexPage.prototype, 'sidebarItems', function (items) {
    items.add('currencyTicker', <CurrencyTicker />, 80);
  });
});
