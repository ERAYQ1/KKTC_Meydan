import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import ShareModal from './ShareModal';

app.initializers.add('kktcmeydan-social-share', () => {
  extend(DiscussionControls, 'userControls', function (items, discussion) {
    items.add(
      'share',
      <Button icon="fas fa-share-alt" onclick={() => app.modal.show(ShareModal, { discussion })}>
        {app.translator.trans('kktcmeydan-social-share.forum.share_button')}
      </Button>
    );
  });
});
