(() => {
  const qs = (selector) => document.querySelector(selector);
  const qsa = (selector) => Array.from(document.querySelectorAll(selector));

  const nameInput = qs('[data-staark-brand-name]');
  const taglineInput = qs('[data-staark-brand-tagline]');
  const primaryInput = qs('[data-staark-brand-primary]');
  const inkInput = qs('[data-staark-brand-ink]');
  const preview = qs('[data-staark-brand-preview]');

  const syncPreview = () => {
    if (nameInput) {
      qsa('[data-staark-preview-name]').forEach((node) => {
        node.textContent = nameInput.value.trim() || 'Your brand';
      });
    }

    if (taglineInput) {
      qsa('[data-staark-preview-tagline]').forEach((node) => {
        node.textContent = taglineInput.value.trim() || 'A clear tagline for the website.';
      });
    }

    if (preview && primaryInput) {
      preview.style.setProperty('--brand-primary', primaryInput.value);
      const value = qs('[data-staark-color-value="primary"]');
      if (value) value.textContent = primaryInput.value;
    }

    if (preview && inkInput) {
      preview.style.setProperty('--brand-ink', inkInput.value);
      const value = qs('[data-staark-color-value="ink"]');
      if (value) value.textContent = inkInput.value;
    }
  };

  [nameInput, taglineInput, primaryInput, inkInput].filter(Boolean).forEach((input) => {
    input.addEventListener('input', syncPreview);
  });

  qsa('[data-staark-media-button]').forEach((button) => {
    button.addEventListener('click', () => {
      if (!window.wp || !wp.media) return;

      const target = qs(button.dataset.target || '');
      const previewTarget = qs(button.dataset.preview || '');
      if (!target || !previewTarget) return;

      const frame = wp.media({
        title: button.dataset.title || 'Choose image',
        button: { text: 'Use image' },
        library: { type: 'image' },
        multiple: false,
      });

      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        target.value = attachment.id || '';
        const url = attachment.sizes?.medium?.url || attachment.sizes?.thumbnail?.url || attachment.url;
        const image = document.createElement('img');
        image.src = url;
        image.alt = '';
        previewTarget.replaceChildren(image);

        if (target.id === 'staark_brand_logo_id') {
          const liveMark = qs('[data-staark-preview-mark]');
          if (liveMark) {
            const liveImage = document.createElement('img');
            liveImage.src = url;
            liveImage.alt = '';
            liveMark.replaceChildren(liveImage);
          }
        }
      });

      frame.open();
    });
  });

  qsa('[data-staark-media-remove]').forEach((button) => {
    button.addEventListener('click', () => {
      const target = qs(button.dataset.target || '');
      const previewTarget = qs(button.dataset.preview || '');
      if (!target || !previewTarget) return;

      target.value = '0';
      const placeholder = document.createElement('div');
      placeholder.className = 'staark-hub-asset-placeholder';
      placeholder.textContent = 'No image selected';
      previewTarget.replaceChildren(placeholder);

      if (target.id === 'staark_brand_logo_id') {
        const liveMark = qs('[data-staark-preview-mark]');
        if (liveMark) {
          liveMark.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 16 4-4-4-4"></path><path d="m6 8-4 4 4 4"></path><path d="m14.5 4-5 16"></path></svg>';
        }
      }
    });
  });

  syncPreview();
})();
