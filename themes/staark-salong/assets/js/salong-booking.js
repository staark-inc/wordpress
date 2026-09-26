/**
 * S-Hub Salong — booking request enhancer.
 *
 * Progressive enhancement for the Staark Hub contact form inside a
 * `.salong-booking` section. Adds service, date, time and "new customer"
 * fields and folds them into the Hub's required message field on submit, so
 * requests arrive in Staark Hub → Forms without any server-side changes.
 * Without JavaScript the plain Hub form still works.
 */
(function () {
  'use strict';

  var TIME_SLOTS = [
    ['', 'Välj tid på dagen'],
    ['Förmiddag (10–12)', 'Förmiddag (10–12)'],
    ['Lunch (12–14)', 'Lunch (12–14)'],
    ['Eftermiddag (14–17)', 'Eftermiddag (14–17)'],
    ['Kväll (17–19)', 'Kväll (17–19)'],
    ['Flexibel', 'Jag är flexibel']
  ];

  function el(tag, attrs, children) {
    var node = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (key) {
      if (key === 'text') {
        node.textContent = attrs[key];
      } else {
        node.setAttribute(key, attrs[key]);
      }
    });
    (children || []).forEach(function (child) {
      node.appendChild(child);
    });
    return node;
  }

  function field(id, label, control, extraClass) {
    control.id = id;
    return el('p', { 'class': 'staark-form-field ' + (extraClass || '') }, [
      el('label', { 'for': id, text: label }),
      control
    ]);
  }

  function localDate(offsetDays) {
    var d = new Date();
    d.setDate(d.getDate() + offsetDays);
    var m = String(d.getMonth() + 1).padStart(2, '0');
    var day = String(d.getDate()).padStart(2, '0');
    return d.getFullYear() + '-' + m + '-' + day;
  }

  function formatDate(value) {
    if (!value) {
      return '';
    }
    var parts = value.split('-');
    var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    try {
      return date.toLocaleDateString('sv-SE', { weekday: 'long', day: 'numeric', month: 'long' }) + ' (' + value + ')';
    } catch (e) {
      return value;
    }
  }

  function services(section) {
    var items = section.querySelectorAll('.salong-booking-services li');
    var list = [];
    items.forEach(function (li) {
      var text = (li.textContent || '').trim();
      if (text) {
        list.push(text);
      }
    });
    if (!list.length) {
      list = ['Klippning', 'Färg', 'Behandling', 'Styling', 'Konsultation'];
    }
    return list;
  }

  function enhance(section) {
    var form = section.querySelector('form.staark-contact-form');
    if (!form || form.dataset.salongEnhanced) {
      return;
    }
    form.dataset.salongEnhanced = '1';
    form.classList.add('salong-booking-enhanced');

    var uid = 'salong-' + Math.random().toString(36).slice(2, 8);
    var message = form.querySelector('textarea[name="message"]');
    var company = form.querySelector('input[name="company"]');
    var grid = form.querySelector('.staark-form-grid');

    // A salon booking has no use for the company field.
    if (company && company.closest('.staark-form-field')) {
      company.closest('.staark-form-field').hidden = true;
      company.disabled = true;
    }

    var serviceSelect = el('select', { name: 'salong_service', required: 'required' }, [
      el('option', { value: '', text: 'Välj behandling' })
    ]);
    services(section).forEach(function (name) {
      serviceSelect.appendChild(el('option', { value: name, text: name }));
    });

    var dateInput = el('input', {
      type: 'date',
      name: 'salong_date',
      min: localDate(0),
      max: localDate(120)
    });

    var timeSelect = el('select', { name: 'salong_time' });
    TIME_SLOTS.forEach(function (slot) {
      timeSelect.appendChild(el('option', { value: slot[0], text: slot[1] }));
    });

    var newCustomer = el('input', { type: 'checkbox', name: 'salong_new', value: '1' });
    var newCustomerRow = el('p', { 'class': 'staark-form-field salong-check' }, [
      el('label', {}, [newCustomer, el('span', { text: 'Jag är ny kund hos er' })])
    ]);

    var booking = el('fieldset', { 'class': 'salong-booking-fields' }, [
      el('legend', { 'class': 'screen-reader-text', text: 'Önskad tid' }),
      field(uid + '-service', 'Behandling *', serviceSelect, 'salong-field-wide'),
      field(uid + '-date', 'Önskat datum', dateInput),
      field(uid + '-time', 'Tid på dagen', timeSelect),
      newCustomerRow
    ]);

    if (grid && grid.parentNode) {
      grid.parentNode.insertBefore(booking, grid);
    } else {
      form.insertBefore(booking, form.firstChild);
    }

    if (message) {
      var messageLabel = form.querySelector('label[for="' + message.id + '"]');
      if (messageLabel) {
        messageLabel.textContent = 'Övrigt (hårlängd, önskemål, frisör)';
      }
      message.required = false;
      message.rows = 4;
      message.placeholder = 'T.ex. axellångt hår, vill ha mjuka lager.';
    }

    form.addEventListener('submit', function () {
      if (!message || message.dataset.salongComposed) {
        return;
      }
      // Structured copy for Staark Hub → Forms & Booking (ignored by older Hub versions).
      [
        ['booking_type', 'appointment'],
        ['booking_date', dateInput.value],
        ['booking_time', timeSelect.value],
        ['booking_item', serviceSelect.value]
      ].forEach(function (pair) {
        var hidden = el('input', { type: 'hidden', name: pair[0] });
        hidden.value = pair[1] || '';
        form.appendChild(hidden);
      });
      var lines = ['Bokningsförfrågan', 'Behandling: ' + (serviceSelect.value || '—')];
      if (dateInput.value) {
        lines.push('Önskat datum: ' + formatDate(dateInput.value));
      }
      if (timeSelect.value) {
        lines.push('Tid på dagen: ' + timeSelect.value);
      }
      lines.push('Ny kund: ' + (newCustomer.checked ? 'Ja' : 'Nej'));
      var note = message.value.trim();
      message.value = lines.join('\n') + (note ? '\n\n' + note : '');
      message.dataset.salongComposed = '1';
    });
  }

  function init() {
    document.querySelectorAll('.salong-booking').forEach(enhance);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
