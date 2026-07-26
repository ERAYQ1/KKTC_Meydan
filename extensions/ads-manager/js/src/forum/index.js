import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Model from 'flarum/common/Model';
import IndexPage from 'flarum/forum/components/IndexPage';
import AdBanner from './AdBanner';

app.initializers.add('kktcmeydan-ads-manager', () => {
  app.store.models.ads = Model;

  extend(IndexPage.prototype, 'contentItems', function (items) {
    items.add('adBanner', <AdBanner />, 95);
  });
});
