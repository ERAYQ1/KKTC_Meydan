export default function addMobileHeaderScroll() {
  let lastScrollY = window.scrollY;
  let ticking = false;

  window.addEventListener(
    'scroll',
    () => {
      if (window.innerWidth > 768) return;

      if (!ticking) {
        window.requestAnimationFrame(() => {
          const currentY = window.scrollY;
          const header = document.getElementById('header');

          if (header) {
            if (currentY > lastScrollY && currentY > 80) {
              header.classList.add('MobileHeader--hidden');
            } else {
              header.classList.remove('MobileHeader--hidden');
            }
          }

          lastScrollY = currentY;
          ticking = false;
        });

        ticking = true;
      }
    },
    { passive: true }
  );
}
