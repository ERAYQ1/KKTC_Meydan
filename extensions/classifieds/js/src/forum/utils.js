import { CURRENCY_SYMBOLS } from './constants';

export function formatPrice(price, currency) {
  const symbol = CURRENCY_SYMBOLS[currency] || currency;
  const formatted = Number(price).toLocaleString('tr-TR', { maximumFractionDigits: 2 });

  return `${formatted} ${symbol}`;
}
