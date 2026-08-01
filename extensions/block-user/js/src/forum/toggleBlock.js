import app from 'flarum/forum/app';

export default function toggleBlock(user) {
  const isBlocked = !!user.attribute('isBlocked');

  if (!isBlocked && !window.confirm(app.translator.trans('kktcmeydan-block-user.forum.block_confirm'))) {
    return Promise.resolve();
  }

  return app
    .request({
      method: isBlocked ? 'DELETE' : 'POST',
      url: `${app.forum.attribute('apiUrl')}/users/${user.id()}/block`,
    })
    .then((response) => {
      app.store.pushPayload(response);

      app.alerts.show(
        { type: 'success' },
        app.translator.trans(isBlocked ? 'kktcmeydan-block-user.forum.unblocked_toast' : 'kktcmeydan-block-user.forum.blocked_toast')
      );

      m.redraw();
    });
}
