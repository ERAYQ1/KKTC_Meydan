import app from 'flarum/forum/app';
import { override } from 'flarum/common/extend';
import PostUser from 'flarum/forum/components/PostUser';
import DiscussionListItem from 'flarum/forum/components/DiscussionListItem';

function trans(key) {
  return app.translator.trans(`kktcmeydan-anonymous-posting.forum.${key}`);
}

function modLabel(model) {
  return model.attribute('anonymousModLabel');
}

export default function maskAnonymousAuthor() {
  override(PostUser.prototype, 'view', function (original) {
    const post = this.attrs.post;

    if (!post.attribute('isAnonymous')) return original();

    const label = modLabel(post);

    return (
      <div className="PostUser PostUser--anonymous">
        <h3 className="PostUser-name">
          <span className="Avatar PostUser-avatar PostUser-avatar--anonymous">
            <i className="fas fa-user-secret" />
          </span>{' '}
          {trans('anonymous_label')}
        </h3>
        {label && (
          <div className="PostUser-anonymousModLabel" title={label}>
            <i className="fas fa-shield-halved" /> {label}
          </div>
        )}
      </div>
    );
  });

  override(DiscussionListItem.prototype, 'authorAvatarView', function (original) {
    const discussion = this.attrs.discussion;

    if (!discussion.attribute('isAnonymous')) return original();

    const label = modLabel(discussion);

    return (
      <span className="DiscussionListItem-author DiscussionListItem-author--anonymous" title={label || trans('anonymous_label')}>
        <span className="Avatar">
          <i className="fas fa-user-secret" />
        </span>
      </span>
    );
  });
}
