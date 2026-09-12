(()=>{'use strict';
if(!document.querySelector('link[data-cms-responsive]')){const responsive=document.createElement('link');responsive.rel='stylesheet';responsive.href='/assets/cms-responsive.css';responsive.dataset.cmsResponsive='1';document.head.append(responsive)}
const root=document.documentElement,buttons=[...document.querySelectorAll('[data-theme-value]')];
const apply=theme=>{if(theme==='auto')root.removeAttribute('data-theme');else root.setAttribute('data-theme',theme);buttons.forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.themeValue===theme)));try{localStorage.setItem('workshop-theme',theme)}catch{}};
let initial=root.getAttribute('data-theme')||'auto';try{const stored=localStorage.getItem('workshop-theme');if(stored&&['auto','light','dark'].includes(stored))initial=stored}catch{}
buttons.forEach(button=>button.addEventListener('click',()=>apply(button.dataset.themeValue)));apply(initial);
const header=document.querySelector('[data-cms-public-header]'),navToggle=header?.querySelector('.cms-nav-toggle');
function setNav(open){if(!header||!navToggle)return;header.classList.toggle('cms-nav-open',open);navToggle.setAttribute('aria-expanded',String(open));}
navToggle?.addEventListener('click',()=>setNav(!header.classList.contains('cms-nav-open')));header?.querySelectorAll('nav a').forEach(link=>link.addEventListener('click',()=>setNav(false)));matchMedia('(min-width:981px)').addEventListener?.('change',event=>{if(event.matches)setNav(false)});
const zone=Intl.DateTimeFormat().resolvedOptions().timeZone;document.querySelectorAll('select[data-timezone-select]').forEach(select=>{if(!select.value&&[...select.options].some(option=>option.value===zone))select.value=zone});
document.addEventListener('click',event=>{const link=event.target.closest('a[href^="#"]');if(!link)return;const id=link.getAttribute('href').slice(1);const target=document.getElementById(id);if(!target)return;event.preventDefault();history.replaceState({},'',`#${id}`);target.scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'start'});});
document.querySelectorAll('.cms-form').forEach(form=>{form.addEventListener('invalid',event=>event.target.setAttribute('aria-invalid','true'),true);form.addEventListener('input',event=>event.target.removeAttribute?.('aria-invalid'));});
})();
