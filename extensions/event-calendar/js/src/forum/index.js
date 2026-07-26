import app from 'flarum/forum/app';
import addEventComposerFields from './addEventComposerFields';
import addEventBadge from './addEventBadge';

app.initializers.add('kktcmeydan-event-calendar', () => {
  addEventComposerFields();
  addEventBadge();
});
