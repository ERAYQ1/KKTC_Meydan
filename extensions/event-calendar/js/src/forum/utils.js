function pad(n) {
  return String(n).padStart(2, '0');
}

export function formatEventDate(isoString) {
  const date = new Date(isoString);

  if (isNaN(date.getTime())) return '';

  return `${pad(date.getDate())}.${pad(date.getMonth() + 1)}.${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}
