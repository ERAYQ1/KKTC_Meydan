/**
 * On mobile, when the virtual keyboard opens under a focused input inside
 * the composer, scroll that input back into view instead of leaving it
 * hidden behind the keyboard.
 */
export default function scrollFocusedInputIntoView() {
  document.addEventListener(
    'focusin',
    (e) => {
      if (window.innerWidth > 768) return;

      const target = e.target;

      if (!target || !target.closest) return;
      if (!target.closest('.Composer')) return;
      if (!/^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName)) return;

      setTimeout(() => {
        target.scrollIntoView({ block: 'center', behavior: 'smooth' });
      }, 300);
    },
    true
  );
}
