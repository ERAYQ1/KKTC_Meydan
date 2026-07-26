import app from 'flarum/forum/app';
import ForumApplication from 'flarum/forum/ForumApplication';
import { extend } from 'flarum/common/extend';
import BottomNav from './BottomNav';
import addMobileHeaderScroll from './addMobileHeaderScroll';
import scrollFocusedInputIntoView from './scrollFocusedInputIntoView';

app.initializers.add('kktcmeydan-mobile-ui', () => {
  extend(ForumApplication.prototype, 'mount', function () {
    if (!document.getElementById('kktcmeydan-mobile-bottom-nav')) {
      const mountEl = document.createElement('div');
      mountEl.id = 'kktcmeydan-mobile-bottom-nav';
      document.body.appendChild(mountEl);
      m.mount(mountEl, { view: () => <BottomNav /> });
    }
  });

  addMobileHeaderScroll();
  scrollFocusedInputIntoView();
});
