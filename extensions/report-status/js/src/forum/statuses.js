import app from 'flarum/forum/app';

// Server-provided (see extend.php's ForumSerializer attribute), sourced from
// SaveReportStatusToDatabase::VALID_STATUSES - the same list the server
// validates against, not a second hand-kept copy. Falls back to a snapshot
// of that list only if an older, un-upgraded server build doesn't send the
// attribute yet.
export function getStatuses() {
  return app.forum.attribute('reportStatuses') || ['bildirildi', 'inceleniyor', 'yetkiliye-iletildi', 'cozuldu'];
}
