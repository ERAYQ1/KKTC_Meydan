import app from 'flarum/admin/app';
import Model from 'flarum/common/Model';
import AdsPage from './AdsPage';

app.initializers.add('kktcmeydan-ads-manager', () => {
  app.store.models.ads = Model;

  app.extensionData.for('kktcmeydan-ads-manager').registerPage(AdsPage);
});
