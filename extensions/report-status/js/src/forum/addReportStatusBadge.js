import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Discussion from 'flarum/common/models/Discussion';
import Badge from 'flarum/common/components/Badge';

export default function addReportStatusBadge() {
  extend(Discussion.prototype, 'badges', function (badges) {
    const status = this.attribute('reportStatus');

    if (status) {
      badges.add(
        'reportStatus',
        <Badge
          type="reportStatus"
          className={`ReportStatusBadge--${status}`}
          icon="fas fa-triangle-exclamation"
          label={app.translator.trans('kktcmeydan-report-status.forum.status.' + status)}
        />,
        20
      );
    }
  });
}
