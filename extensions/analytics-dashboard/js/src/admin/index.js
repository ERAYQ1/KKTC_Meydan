import app from 'flarum/admin/app';
import AnalyticsPage from './AnalyticsPage';

app.initializers.add('kktcmeydan-analytics-dashboard', () => {
  app.extensionData.for('kktcmeydan-analytics-dashboard').registerPage(AnalyticsPage);
});
