import app from 'flarum/forum/app';
import UserPage from 'flarum/forum/components/UserPage';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Button from 'flarum/common/components/Button';
import humanTime from 'flarum/common/utils/humanTime';
import username from 'flarum/common/helpers/username';
import StarRating from './StarRating';

export default class BusinessReviewsPage extends UserPage {
  oninit(vnode) {
    super.oninit(vnode);

    this.reviews = null;
    this.loading = true;
    this.myRating = 0;
    this.myComment = '';
    this.submitLoading = false;

    this.loadUser(m.route.param('username'));
  }

  show(user) {
    super.show(user);

    this.loadReviews();
  }

  loadReviews() {
    this.loading = true;

    app.store
      .find('business-reviews', { filter: { business: this.user.id() } })
      .then((reviews) => {
        this.reviews = reviews;
        this.loading = false;

        const mine = reviews.find((r) => r.reviewer() && r.reviewer().id() === app.session.user?.id());

        if (mine) {
          this.myRating = mine.rating();
          this.myComment = mine.comment() || '';
        }

        m.redraw();
      });
  }

  content() {
    const user = this.user;
    const isBusiness = user.attribute('isBusinessUser');
    const avgRating = user.attribute('businessAvgRating');
    const reviewCount = user.attribute('businessReviewCount') || 0;

    if (!isBusiness) {
      return (
        <div className="BusinessReviews container">
          <p>{app.translator.trans('kktcmeydan-business-profile.forum.reviews.no_reviews')}</p>
        </div>
      );
    }

    return (
      <div className="BusinessReviews container">
        <h2>{app.translator.trans('kktcmeydan-business-profile.forum.reviews.heading')}</h2>

        <div className="BusinessReviews-summary">
          <StarRating value={Math.round(avgRating || 0)} />
          <span>{app.translator.trans('kktcmeydan-business-profile.forum.reviews.review_count', { count: reviewCount })}</span>
        </div>

        {this.loading ? (
          <LoadingIndicator />
        ) : (
          <div>
            {this.reviews.length === 0 && <p>{app.translator.trans('kktcmeydan-business-profile.forum.reviews.no_reviews')}</p>}

            {this.reviews.map((review) => (
              <div className="BusinessReviews-item">
                <strong>{review.reviewer() ? username(review.reviewer()) : '—'}</strong>{' '}
                <StarRating value={review.rating()} />{' '}
                <span className="Post-signature">{humanTime(review.createdAt())}</span>
                {review.comment() && <p>{review.comment()}</p>}
                {app.session.user && review.reviewer() && review.reviewer().id() === app.session.user.id() && (
                  <Button
                    className="Button Button--link"
                    onclick={() => {
                      review.delete().then(() => this.loadReviews());
                    }}
                  >
                    {app.translator.trans('kktcmeydan-business-profile.forum.reviews.delete_button')}
                  </Button>
                )}
              </div>
            ))}
          </div>
        )}

        {app.session.user ? (
          app.session.user.id() === user.id() ? null : (
            <div className="BusinessReviews-form">
              <StarRating value={this.myRating} interactive onchange={(n) => (this.myRating = n)} />
              <div className="Form-group">
                <textarea
                  className="FormControl"
                  placeholder={app.translator.trans('kktcmeydan-business-profile.forum.reviews.comment_label')}
                  value={this.myComment}
                  oninput={(e) => (this.myComment = e.target.value)}
                />
              </div>
              <Button
                className="Button Button--primary"
                loading={this.submitLoading}
                disabled={!this.myRating}
                onclick={() => {
                  this.submitLoading = true;

                  app.store
                    .createRecord('business-reviews')
                    .save({
                      businessUserId: user.id(),
                      rating: this.myRating,
                      comment: this.myComment,
                    })
                    .then(() => {
                      this.submitLoading = false;
                      this.loadReviews();
                    })
                    .catch(() => {
                      this.submitLoading = false;
                      m.redraw();
                    });
                }}
              >
                {app.translator.trans('kktcmeydan-business-profile.forum.reviews.add_review')}
              </Button>
            </div>
          )
        ) : (
          <p>{app.translator.trans('kktcmeydan-business-profile.forum.reviews.login_required')}</p>
        )}
      </div>
    );
  }
}
