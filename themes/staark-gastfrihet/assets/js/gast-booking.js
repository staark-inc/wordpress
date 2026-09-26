/**
 * S-Hub Gästfrihet — booking request enhancer.
 *
 * Progressive enhancement for the Staark Hub contact form inside a
 * `.gast-booking` section:
 * - restaurant (`.gast-booking--restaurang`): date, time, guests, occasion;
 * - hotel (`.gast-booking--hotell`): check-in, check-out, adults, children,
 *   room type and number of rooms.
 *
 * Time slots / room types come from the hidden `.gast-booking-options` list
 * in the pattern, so they are edited in the block editor. On submit the
 * details are folded into the Hub's required message field, so requests
 * arrive in Staark Hub → Forms without server-side changes. Without
 * JavaScript the plain Hub form still works.
 */
(function () {
  'use strict';

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

  function select(name, options, required) {
    var node = el('select', required ? { name: name, required: 'required' } : { name: name });
    options.forEach(function (option) {
      node.appendChild(el('option', { value: option[0], text: option[1] }));
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

  function pad(n) {
    return (n < 10 ? '0' : '') + n;
  }

  function iso(date) {
    return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
  }

  function parse(value) {
    var parts = (value || '').split('-');
    if (parts.length !== 3) {
      return null;
    }
    var date = new Date(+parts[0], +parts[1] - 1, +parts[2]);
    return isNaN(date.getTime()) ? null : date;
  }

  function addDays(date, days) {
    var copy = new Date(date.getTime());
    copy.setDate(copy.getDate() + days);
    return copy;
  }

  function nice(value) {
    var date = parse(value);
    if (!date) {
      return value || '—';
    }
    try {
      return date.toLocaleDateString('sv-SE', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) + ' (' + value + ')';
    } catch (e) {
      return value;
    }
  }

  function range(from, to, suffix) {
    var list = [];
    for (var i = from; i <= to; i++) {
      list.push([String(i), i + (suffix ? ' ' + (i === 1 ? suffix[0] : suffix[1]) : '')]);
    }
    return list;
  }

  function listOptions(section) {
    var list = [];
    section.querySelectorAll('.gast-booking-options li').forEach(function (li) {
      var text = (li.textContent || '').trim();
      if (text) {
        list.push([text, text]);
      }
    });
    return list;
  }

  /**
   * Structured copy of the booking for Staark Hub → Forms & Booking
   * (booking_* fields; older Hub versions ignore them).
   */
  function setHidden(form, name, value) {
    var input = form.querySelector('input[type="hidden"][name="' + name + '"]');
    if (!input) {
      input = el('input', { type: 'hidden', name: name });
      form.appendChild(input);
    }
    input.value = value == null ? '' : String(value);
  }

  function insertFields(form, fields) {
    var grid = form.querySelector('.staark-form-grid');
    if (grid && grid.parentNode) {
      grid.parentNode.insertBefore(fields, grid);
    } else {
      form.insertBefore(fields, form.firstChild);
    }
  }

  function relabel(form, control, text) {
    var label = control ? form.querySelector('label[for="' + control.id + '"]') : null;
    if (label) {
      label.textContent = text;
    }
  }

  function restaurant(section, form, uid, today) {
    var times = listOptions(section);
    if (!times.length) {
      times = [['12:00', '12:00'], ['18:00', '18:00'], ['19:00', '19:00'], ['20:00', '20:00']];
    }

    var date = el('input', { type: 'date', name: 'gast_date', required: 'required', min: iso(today), value: iso(today) });
    var time = select('gast_time', [['', 'Välj tid']].concat(times), true);
    var guests = select('gast_guests', [['', 'Antal']].concat(range(1, 10, ['person', 'personer'])).concat([['Fler än 10', 'Fler än 10']]), true);
    var occasion = select('gast_occasion', [
      ['', 'Inget särskilt'],
      ['Födelsedag', 'Födelsedag'],
      ['Jubileum / årsdag', 'Jubileum / årsdag'],
      ['Affärsmiddag', 'Affärsmiddag'],
      ['Dejt', 'Dejt'],
      ['Annat', 'Annat']
    ], false);
    var large = el('p', { 'class': 'gast-field-hint gast-field-wide', hidden: 'hidden', text: 'Större sällskap: vi återkommer med förslag på meny och placering.' });

    guests.addEventListener('change', function () {
      large.hidden = guests.value !== 'Fler än 10';
    });

    insertFields(form, el('fieldset', { 'class': 'gast-booking-fields' }, [
      el('legend', { 'class': 'screen-reader-text', text: 'Bordsbokning' }),
      field(uid + '-date', 'Datum *', date),
      field(uid + '-time', 'Tid *', time),
      field(uid + '-guests', 'Antal gäster *', guests),
      field(uid + '-occasion', 'Tillfälle', occasion),
      large
    ]));

    return function () {
      setHidden(form, 'booking_type', 'table');
      setHidden(form, 'booking_date', date.value);
      setHidden(form, 'booking_time', time.value);
      setHidden(form, 'booking_guests', guests.value === 'Fler än 10' ? 11 : guests.value);
      setHidden(form, 'booking_item', occasion.value);
      var lines = [
        'Bordsbokning (förfrågan)',
        'Datum: ' + nice(date.value),
        'Tid: ' + (time.value || '—'),
        'Antal gäster: ' + (guests.value || '—')
      ];
      if (occasion.value) {
        lines.push('Tillfälle: ' + occasion.value);
      }
      return lines;
    };
  }

  function hotel(section, form, uid, today) {
    var rooms = listOptions(section);
    var checkin = el('input', { type: 'date', name: 'gast_checkin', required: 'required', min: iso(today), value: iso(today) });
    var checkout = el('input', { type: 'date', name: 'gast_checkout', required: 'required', min: iso(addDays(today, 1)), value: iso(addDays(today, 1)) });
    var adults = select('gast_adults', range(1, 8), true);
    var children = select('gast_children', range(0, 4), false);
    var room = select('gast_room', [['', 'Välj rumstyp']].concat(rooms).concat([['Vet inte – ge förslag', 'Vet inte – ge förslag']]), true);
    var count = select('gast_rooms', range(1, 6, ['rum', 'rum']), false);
    var nights = el('p', { 'class': 'gast-field-hint gast-field-wide', 'aria-live': 'polite' });

    adults.value = '2';

    function sync() {
      var inDate = parse(checkin.value);
      var outDate = parse(checkout.value);
      if (inDate) {
        var min = addDays(inDate, 1);
        checkout.min = iso(min);
        if (!outDate || outDate < min) {
          checkout.value = iso(min);
          outDate = min;
        }
      }
      var n = inDate && outDate ? Math.round((outDate - inDate) / 86400000) : 0;
      checkout.setCustomValidity(n > 0 ? '' : 'Utcheckning måste vara efter incheckning.');
      nights.textContent = n > 0 ? n + (n === 1 ? ' natt' : ' nätter') + ' · frukost ingår' : '';
    }

    checkin.addEventListener('change', sync);
    checkout.addEventListener('change', sync);
    sync();

    insertFields(form, el('fieldset', { 'class': 'gast-booking-fields' }, [
      el('legend', { 'class': 'screen-reader-text', text: 'Rumsbokning' }),
      field(uid + '-in', 'Incheckning *', checkin),
      field(uid + '-out', 'Utcheckning *', checkout),
      nights,
      field(uid + '-adults', 'Vuxna *', adults),
      field(uid + '-children', 'Barn (0–12 år)', children),
      field(uid + '-room', 'Rumstyp *', room),
      field(uid + '-count', 'Antal rum', count)
    ]));

    return function () {
      setHidden(form, 'booking_type', 'room');
      setHidden(form, 'booking_date', checkin.value);
      setHidden(form, 'booking_end_date', checkout.value);
      setHidden(form, 'booking_guests', (+adults.value || 0) + (+children.value || 0));
      setHidden(form, 'booking_item', room.value + (count.value !== '1' ? ' × ' + count.value : ''));
      var lines = [
        'Rumsbokning (förfrågan)',
        'Incheckning: ' + nice(checkin.value),
        'Utcheckning: ' + nice(checkout.value)
      ];
      if (nights.textContent) {
        lines.push('Nätter: ' + nights.textContent.split(' · ')[0]);
      }
      lines.push('Gäster: ' + adults.value + ' vuxna' + (children.value !== '0' ? ', ' + children.value + ' barn' : ''));
      lines.push('Rumstyp: ' + (room.value || '—'));
      lines.push('Antal rum: ' + count.value);
      return lines;
    };
  }

  function enhance(section) {
    var form = section.querySelector('form.staark-contact-form');
    if (!form || form.dataset.gastEnhanced) {
      return;
    }
    form.dataset.gastEnhanced = '1';

    var isHotel = section.classList.contains('gast-booking--hotell');
    var uid = 'gast-' + Math.random().toString(36).slice(2, 8);
    var today = new Date();
    today.setHours(0, 0, 0, 0);

    var message = form.querySelector('textarea[name="message"]');
    var company = form.querySelector('input[name="company"]');
    var phone = form.querySelector('input[name="phone"]');

    var compose = isHotel ? hotel(section, form, uid, today) : restaurant(section, form, uid, today);

    if (company) {
      if (isHotel) {
        relabel(form, company, 'Företag (valfritt, för faktura)');
      } else if (company.closest('.staark-form-field')) {
        company.closest('.staark-form-field').hidden = true;
      }
    }

    if (phone) {
      relabel(form, phone, isHotel ? 'Telefon' : 'Telefon (för bekräftelse)');
    }

    if (message) {
      relabel(form, message, 'Önskemål (valfritt)');
      message.removeAttribute('required');
      message.rows = 4;
      message.placeholder = isHotel
        ? 'Ankomsttid, extrasäng, allergier, paket …'
        : 'Allergier, barnstol, fönsterbord …';
    }

    form.addEventListener('submit', function () {
      if (!message || message.dataset.gastComposed) {
        return;
      }
      var note = message.value.trim();
      message.value = compose().join('\n') + (note ? '\n\nÖnskemål:\n' + note : '');
      message.dataset.gastComposed = '1';
    });
  }

  function init() {
    document.querySelectorAll('.gast-booking').forEach(enhance);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
