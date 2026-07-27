import app from 'flarum/forum/app';

// Tags that trigger the classified fields in the discussion composer.
export const CLASSIFIED_TRIGGER_SLUGS = ['yasam', 'satilik', 'kiralik', 'is-ilani', 'ev-arkadasi', 'ikinci-el'];

// Server-provided (see extend.php's ForumSerializer attribute), sourced from
// SaveClassifiedFieldsToDatabase::VALID_CURRENCIES/VALID_TYPES - the same
// list the server validates against, not a second hand-kept copy. Falls
// back to a snapshot of that list only if an older, un-upgraded server
// build doesn't send the attribute yet.
export function getCurrencies() {
  return app.forum.attribute('classifiedCurrencies') || ['TRY', 'GBP', 'USD', 'EUR'];
}

export function getClassifiedTypes() {
  return app.forum.attribute('classifiedTypes') || ['satilik', 'kiralik', 'is_ilani', 'ev_arkadasi', 'ikinci_el'];
}

export const CURRENCY_SYMBOLS = {
  TRY: 'TL',
  GBP: '£',
  USD: '$',
  EUR: '€',
};
