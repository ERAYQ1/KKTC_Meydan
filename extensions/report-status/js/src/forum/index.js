import app from 'flarum/forum/app';
import addReportStatusBadge from './addReportStatusBadge';
import addReportStatusControl from './addReportStatusControl';

app.initializers.add('kktcmeydan-report-status', () => {
  addReportStatusBadge();
  addReportStatusControl();
});
