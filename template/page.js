const uiAssetQuery=(()=>{try{return new URL(document.currentScript?.src||location.href).search}catch{return ''}})();
import(`/assets/ui-core.js${uiAssetQuery}`).catch(()=>{});

const embedded = new URLSearchParams(location.search).has('editor') || new URLSearchParams(location.search).has('preview');

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
