import Model from 'flarum/common/Model';

export default class BusinessReview extends Model {}

Object.assign(BusinessReview.prototype, {
  rating: Model.attribute('rating'),
  comment: Model.attribute('comment'),
  createdAt: Model.attribute('createdAt', Model.transformDate),
  reviewer: Model.hasOne('reviewer'),
});
