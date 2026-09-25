/**
 * Staark Hub UI 2 — small shared helpers.
 *
 * [data-staark-copy] buttons copy their value (e.g. the Site ID used when
 * requesting a pairing code) to the clipboard.
 */
(function () {
  'use strict';

  function fallbackCopy(text) {
    var field = document.createElement('textarea');
    field.value = text;
    field.setAttribute('readonly', '');
    field.style.position = 'fixed';
    field.style.opacity = '0';
    document.body.appendChild(field);
    field.select();
    var ok = false;
    try {
      ok = document.execCommand('copy');
    } catch (e) {
      ok = false;
    }
    document.body.removeChild(field);
    return ok ? Promise.resolve() : Promise.reject(new Error('copy failed'));
  }

  function copy(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text).catch(function () {
        return fallbackCopy(text);
      });
    }
    return fallbackCopy(text);
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest ? event.target.closest('[data-staark-copy]') : null;
    if (!button) {
      return;
    }

    var label = button.textContent;
    copy(button.getAttribute('data-staark-copy') || '').then(function () {
      button.textContent = button.getAttribute('data-staark-copied') || 'Copied';
      button.classList.add('is-copied');
      window.setTimeout(function () {
        button.textContent = label;
        button.classList.remove('is-copied');
      }, 1800);
    }).catch(function () {
      window.prompt('Copy:', button.getAttribute('data-staark-copy') || '');
    });
  });
})();
