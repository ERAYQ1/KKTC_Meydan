import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

function trans(key, params) {
  return app.translator.trans(`kktcmeydan-analytics-dashboard.admin.page.${key}`, params);
}

export default class AnalyticsPage extends ExtensionPage {
  oninit(vnode) {
    super.oninit(vnode);

    this.loading = true;
    this.summary = null;

    app
      .request({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/analytics/summary`,
      })
      .then((summary) => {
        this.summary = summary;
        this.loading = false;
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }

  content() {
    if (this.loading) {
      return (
        <div className="AnalyticsPage container">
          <LoadingIndicator />
        </div>
      );
    }

    if (!this.summary) {
      return (
        <div className="AnalyticsPage container">
          <p className="helpText">{trans('empty')}</p>
        </div>
      );
    }

    const s = this.summary;

    return (
      <div className="AnalyticsPage container">
        <div className="AnalyticsPage-cards">
          {this.card(trans('cards.total_discussions'), s.totalDiscussions)}
          {this.card(trans('cards.total_posts'), s.totalPosts)}
          {this.card(trans('cards.total_users'), s.totalUsers)}
          {this.card(trans('cards.dau'), s.dau)}
          {this.card(trans('cards.wau'), s.wau)}
        </div>

        <div className="AnalyticsPage-sections">
          <div>
            <h3>{trans('sections.popular_by_discussions')}</h3>
            {this.barList(s.popularByDiscussions, 'count_discussions')}
          </div>
          <div>
            <h3>{trans('sections.popular_by_posts')}</h3>
            {this.barList(s.popularByPosts, 'count_posts')}
          </div>
        </div>
      </div>
    );
  }

  card(label, value) {
    return (
      <div className="AnalyticsCard">
        <div className="AnalyticsCard-value">{value}</div>
        <div className="AnalyticsCard-label">{label}</div>
      </div>
    );
  }

  barList(rows, countTransKey) {
    if (!rows || !rows.length) {
      return <p className="helpText">{trans('empty')}</p>;
    }

    const max = Math.max(...rows.map((r) => r.count), 1);

    return (
      <div className="AnalyticsBarList">
        {rows.map((row) => (
          <div className="AnalyticsBarList-row">
            <div className="AnalyticsBarList-name">{row.name}</div>
            <div className="AnalyticsBarList-track">
              <div className="AnalyticsBarList-bar" style={{ width: `${(row.count / max) * 100}%` }} />
            </div>
            <div className="AnalyticsBarList-count">{trans(countTransKey, { count: row.count })}</div>
          </div>
        ))}
      </div>
    );
  }
}
