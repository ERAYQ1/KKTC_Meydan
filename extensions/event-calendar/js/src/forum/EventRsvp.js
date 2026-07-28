import Model from 'flarum/common/Model';

export default class EventRsvp extends Model {}

Object.assign(EventRsvp.prototype, {
  discussionId: Model.attribute('discussionId'),
  status: Model.attribute('status'),
  createdAt: Model.attribute('createdAt', Model.transformDate),
  user: Model.hasOne('user'),
});
