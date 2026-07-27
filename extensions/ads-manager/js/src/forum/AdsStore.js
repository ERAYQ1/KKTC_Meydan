import app from 'flarum/forum/app';

/**
 * `<AdBanner>` used to call `app.store.find('ads', ...)` in its own
 * `oninit`, which re-fires on every fresh `IndexPage` instance (tag
 * navigation, "back to discussions", etc.) - an avoidable API round-trip on
 * every navigation for a list that's admin-managed and rarely changes.
 *
 * This module fetches the (small, admin-curated) active-ads list ONCE per
 * page load and shares that single promise across every `AdBanner`
 * instance; banners filter the cached list client-side per tag instead of
 * each re-querying the server.
 */
let adsPromise = null;

export function getAds() {
  if (!adsPromise) {
    adsPromise = app.store.find('ads', {}).catch((error) => {
      // Let a failed fetch be retried on the next call instead of caching
      // the rejection forever.
      adsPromise = null;

      throw error;
    });
  }

  return adsPromise;
}

export function matchesTag(ad, tag) {
  if (!tag) {
    return !ad.attribute('targetCategorySlug') && !ad.attribute('targetDistrictSlug') && !ad.attribute('targetUniversitySlug');
  }

  return (
    ad.attribute('targetCategorySlug') === tag || ad.attribute('targetDistrictSlug') === tag || ad.attribute('targetUniversitySlug') === tag
  );
}
