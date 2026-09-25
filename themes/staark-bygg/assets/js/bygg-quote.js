/**
 * S-Hub Bygg — quote request enhancer.
 *
 * Progressive enhancement for the Staark Hub contact form inside a
 * `.bygg-quote` section. Adds job type, location, preferred start, budget,
 * property type and ROT fields, and folds them into the Hub's required
 * message field on submit, so requests arrive in Staark Hub → Forms without
 * server-side changes. Without JavaScript the plain Hub form still works.
 */
(function () {
  'use strict';

  var START = [
    ['', 'Välj önskad start'],
    ['Så snart som möjligt', 'Så snart som möjligt'],
    ['Inom 1–3 månader', 'Inom 1–3 månader'],
    ['Om 3–6 månader', 'Om 3–6 månader'],
    ['Senare / planerar', 'Senare – jag planerar'],
  ];

  var BUDGET = [
    ['', 'Ungefärlig budget (valfritt)'],
    ['Under 50 000 kr', 'Under 50 000 kr'],
    ['50 000–150 000 kr', '50 000–150 000 kr'],
    ['150 000–400 000 kr', '150 000–400 000 kr'],
    ['Över 400 000 kr', 'Över 400 000 kr'],
    ['Vet inte ännu', 'Vet inte ännu'],
  ];

  var PROPERTY = [
    ['', 'Typ av fastighet'],
    ['Villa / radhus', 'Villa / radhus'],
    ['Bostadsrätt', 'Bostadsrätt'],
    ['Fritidshus', 'Fritidshus'],
    ['BRF / fastighetsägare', 'BRF / fastighetsägare'],
    ['Företag / lokal', 'Företag / lokal'],
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

  function jobTypes(section) {
    var list = [];
    section.querySelectorAll('.bygg-quote-types li').forEach(function (li) {
      var text = (li.textContent || '').trim();
      if (text) {
        list.push([text, text]);
      }
    });
    if (!list.length) {
      list = [['Renovering', 'Renovering'], ['Tillbyggnad', 'Tillbyggnad'], ['Badrum', 'Badrum'], ['Kök', 'Kök'], ['Annat', 'Annat']];
    }
    return [['', 'Välj typ av jobb']].concat(list);
  }

  function enhance(section) {
    var form = section.querySelector('form.staark-contact-form');
    if (!form || form.dataset.byggEnhanced) {
      return;
    }
    form.dataset.byggEnhanced = '1';

    var uid = 'bygg-' + Math.random().toString(36).slice(2, 8);
    var message = form.querySelector('textarea[name="message"]');
    var company = form.querySelector('input[name="company"]');
    var grid = form.querySelector('.staark-form-grid');

    if (company) {
      var companyLabel = form.querySelector('label[for="' + company.id + '"]');
      if (companyLabel) {
        companyLabel.textContent = 'Företag / BRF (valfritt)';
      }
    }

    var jobSelect = select('bygg_job', jobTypes(section), true);
    var place = el('input', { type: 'text', name: 'bygg_place', required: 'required', autocomplete: 'address-level2', placeholder: 'T.ex. Värnamo eller 331 30', maxlength: '120' });
    var startSelect = select('bygg_start', START, false);
    var budgetSelect = select('bygg_budget', BUDGET, false);
    var propertySelect = select('bygg_property', PROPERTY, false);
    var rot = el('input', { type: 'checkbox', name: 'bygg_rot', value: '1', checked: 'checked' });

    var fields = el('fieldset', { 'class': 'bygg-quote-fields' }, [
      el('legend', { 'class': 'screen-reader-text', text: 'Om jobbet' }),
      field(uid + '-job', 'Typ av jobb *', jobSelect),
      field(uid + '-place', 'Ort / postnummer *', place),
      field(uid + '-property', 'Fastighet', propertySelect),
      field(uid + '-start', 'Önskad start', startSelect),
      field(uid + '-budget', 'Budget', budgetSelect, 'bygg-field-wide'),
      el('p', { 'class': 'staark-form-field bygg-check' }, [
        el('label', {}, [rot, el('span', { text: 'Jag vill använda ROT-avdrag (privatperson)' })])
      ])
    ]);

    if (grid && grid.parentNode) {
      grid.parentNode.insertBefore(fields, grid);
    } else {
      form.insertBefore(fields, form.firstChild);
    }

    if (message) {
      var messageLabel = form.querySelector('label[for="' + message.id + '"]');
      if (messageLabel) {
        messageLabel.textContent = 'Beskriv jobbet *';
      }
      message.rows = 5;
      message.placeholder = 'Vad ska göras, ungefärliga mått, önskemål om material …';
    }

    form.addEventListener('submit', function () {
      if (!message || message.dataset.byggComposed) {
        return;
      }
      var lines = [
        'Offertförfrågan',
        'Typ av jobb: ' + (jobSelect.value || '—'),
        'Ort / postnummer: ' + (place.value.trim() || '—')
      ];
      if (propertySelect.value) {
        lines.push('Fastighet: ' + propertySelect.value);
      }
      if (startSelect.value) {
        lines.push('Önskad start: ' + startSelect.value);
      }
      if (budgetSelect.value) {
        lines.push('Budget: ' + budgetSelect.value);
      }
      lines.push('ROT-avdrag: ' + (rot.checked ? 'Ja' : 'Nej'));
      message.value = lines.join('\n') + '\n\n' + message.value.trim();
      message.dataset.byggComposed = '1';
    });
  }

  function init() {
    document.querySelectorAll('.bygg-quote').forEach(enhance);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
