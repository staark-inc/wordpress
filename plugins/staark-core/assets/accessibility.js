(() => {
  'use strict';

  const ready = (callback) => {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback, { once: true });
      return;
    }

    callback();
  };

  ready(() => {
    const hub = document.querySelector('.staark-hub-wrap');
    if (!hub) {
      return;
    }

    // Horizontal data regions must also be reachable without a mouse/touchpad.
    hub.querySelectorAll('.staark-hub-support-table-wrap').forEach((region) => {
      if (!region.hasAttribute('tabindex')) {
        region.setAttribute('tabindex', '0');
      }
      if (!region.hasAttribute('role')) {
        region.setAttribute('role', 'region');
      }
      if (!region.hasAttribute('aria-label')) {
        region.setAttribute('aria-label', 'Support requests table');
      }
      region.classList.add('staark-hub-scroll-region');
    });

    // Keep the active/focused tab visible when the navigation overflows on mobile.
    const nav = hub.querySelector('.staark-hub-nav');
    if (nav) {
      nav.addEventListener('focusin', (event) => {
        const link = event.target.closest('.staark-hub-nav-link');
        if (!link) {
          return;
        }

        link.scrollIntoView({ block: 'nearest', inline: 'nearest' });
      });
    }

    // Links opening another browsing context should announce that behavior.
    hub.querySelectorAll('a[target="_blank"]').forEach((link) => {
      if (link.hasAttribute('aria-label')) {
        return;
      }

      const label = (link.textContent || '').trim();
      if (label) {
        link.setAttribute('aria-label', `${label} (opens in a new tab)`);
      }
    });
  });
})();
