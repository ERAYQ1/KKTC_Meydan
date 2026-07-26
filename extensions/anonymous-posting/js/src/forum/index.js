import app from 'flarum/forum/app';
import addAnonymousComposerCheckbox from './addAnonymousComposerCheckbox';
import maskAnonymousAuthor from './maskAnonymousAuthor';

app.initializers.add('kktcmeydan-anonymous-posting', () => {
  addAnonymousComposerCheckbox();
  maskAnonymousAuthor();
});
