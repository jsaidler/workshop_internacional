const root = document.documentElement;
    const themeButtons = [...document.querySelectorAll('[data-theme-value]')];
    const embedded = new URLSearchParams(location.search).has('editor') || new URLSearchParams(location.search).has('preview');
    const savedTheme = embedded ? 'auto' : (localStorage.getItem('workshop-theme') || 'auto');

    function applyTheme(theme) {
      if (theme === 'auto') root.removeAttribute('data-theme');
      else root.setAttribute('data-theme', theme);
      themeButtons.forEach(button => {
        button.setAttribute('aria-pressed', String(button.dataset.themeValue === theme));
      });
      if (!embedded) localStorage.setItem('workshop-theme', theme);
    }

    themeButtons.forEach(button => button.addEventListener('click', () => applyTheme(button.dataset.themeValue)));
    applyTheme(savedTheme);

    if (new URLSearchParams(location.search).has('editor')) {
      document.querySelectorAll('video').forEach(video => {
        video.autoplay = false;
        video.pause();
      });
    }

    document.querySelectorAll('img[data-fallback]').forEach(img => {
      img.addEventListener('error', () => {
        if (img.src.endsWith(img.dataset.fallback)) return;
        img.src = img.dataset.fallback;
      }, { once: true });
    });

    const form = document.querySelector('.interest-form');
    const status = document.querySelector('.form-status');
    form?.addEventListener('submit', event => {
      event.preventDefault();
      if (!form.checkValidity()) {
        form.reportValidity();
        status.textContent = 'Please complete the required fields.';
        return;
      }
      status.textContent = 'Prototype only: the form is not connected to the CMS yet.';
    });
