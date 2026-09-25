/**
 * S-Hub Salong — small UI helpers.
 *
 * Closes the mobile overlay menu when a same-page anchor (/#priser, #boka …)
 * is chosen, so the visitor lands on the section instead of behind the menu.
 */
(function () {
  'use strict';

  document.addEventListener('click', function (event) {
    var link = event.target.closest ? event.target.closest('.wp-block-navigation__responsive-container.is-menu-open a[href*="#"]') : null;
    if (!link) {
      return;
    }

    var url = new URL(link.href, window.location.href);
    if (url.pathname !== window.location.pathname || !url.hash) {
      return;
    }

    var container = link.closest('.wp-block-navigation__responsive-container');
    var close = container && container.querySelector('.wp-block-navigation__responsive-container-close');
    if (close) {
      close.click();
    }
  });
})();
