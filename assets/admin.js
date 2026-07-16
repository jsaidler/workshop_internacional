document.querySelectorAll('.submission-toggle').forEach((button) => {
  button.addEventListener('click', () => {
    const detail = document.getElementById(button.getAttribute('aria-controls'));
    const open = button.getAttribute('aria-expanded') === 'true';
    button.setAttribute('aria-expanded', String(!open));
    detail.hidden = open;
  });
});

const menuToggle = document.querySelector('.admin-menu-toggle');
const navigation = document.querySelector('#admin-navigation');
if (menuToggle && navigation) {
  menuToggle.addEventListener('click', () => {
    const open = menuToggle.getAttribute('aria-expanded') === 'true';
    menuToggle.setAttribute('aria-expanded', String(!open));
    navigation.classList.toggle('is-open', !open);
  });
}
