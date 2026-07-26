import app from 'flarum/forum/app';
import addClassifiedComposerFields from './addClassifiedComposerFields';
import addClassifiedBadges from './addClassifiedBadges';

app.initializers.add('kktcmeydan-classifieds', () => {
  addClassifiedComposerFields();
  addClassifiedBadges();
});
