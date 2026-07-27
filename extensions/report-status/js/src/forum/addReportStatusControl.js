import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import Dropdown from 'flarum/common/components/Dropdown';
import Button from 'flarum/common/components/Button';
import { getStatuses } from './statuses';

export default function addReportStatusControl() {
  extend(DiscussionControls, 'moderationControls', function (items, discussion) {
    if (!discussion.attribute('canEditReportStatus')) {
      return;
    }

    const current = discussion.attribute('reportStatus');

    items.add(
      'reportStatus',
      <Dropdown
        icon="fas fa-triangle-exclamation"
        buttonClassName="Button"
        label={
          current
            ? app.translator.trans('kktcmeydan-report-status.forum.status.' + current)
            : app.translator.trans('kktcmeydan-report-status.forum.discussion_controls.heading')
        }
      >
        {getStatuses().map((status) => (
          <Button
            icon={status === current ? 'fas fa-check' : undefined}
            onclick={() => DiscussionControls.reportStatusAction(discussion, status)}
          >
            {app.translator.trans('kktcmeydan-report-status.forum.status.' + status)}
          </Button>
        ))}
        {current && (
          <Button onclick={() => DiscussionControls.reportStatusAction(discussion, null)}>
            {app.translator.trans('kktcmeydan-report-status.forum.discussion_controls.clear_button')}
          </Button>
        )}
      </Dropdown>
    );
  });

  DiscussionControls.reportStatusAction = function (discussion, status) {
    discussion.save({ reportStatus: status }).then(() => {
      if (app.current.matches(DiscussionPage)) {
        app.current.get('stream').update();
      }

      m.redraw();
    });
  };
}
