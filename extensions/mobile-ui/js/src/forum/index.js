import app from 'flarum/forum/app';
import BottomNav from './BottomNav';
import addMobileHeaderScroll from './addMobileHeaderScroll';
import scrollFocusedInputIntoView from './scrollFocusedInputIntoView';

app.initializers.add('kktcmeydan-mobile-ui', () => {
  const mountEl = document.createElement('div');
  mountEl.id = 'kktcmeydan-mobile-bottom-nav';
  document.body.appendChild(mountEl);
  m.mount(mountEl, { view: () => <BottomNav /> });

  addMobileHeaderScroll();
  scrollFocusedInputIntoView();
});
