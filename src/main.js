import './styles/main.css';
import { registerRoute, startRouter, navigate } from './router.js';
import { renderHome } from './pages/home.js';
import { renderCheckin } from './pages/checkin.js';
import { renderCheckout } from './pages/checkout.js';
import { renderConfirmation } from './pages/confirmation.js';
import { renderConfirmAction } from './pages/confirm-action.js';
import { renderAdmin } from './pages/admin.js';
import { t, getLang, toggleLang } from './i18n.js';

function renderLayout() {
  const lang = getLang();
  const footer = document.createElement('div');
  footer.id = 'kiosk-footer';
  footer.className = 'kiosk-footer';
  footer.innerHTML = `
    <div class="footer-inner">
      <div class="footer-left">
        <div class="footer-logo">SAP</div>
        <span class="footer-text">${t('siteName')}</span>
      </div>
      <div class="footer-right">
        <span class="footer-clock" id="clock">--:--</span>
      </div>
    </div>
  `;

  const langBtn = document.createElement('button');
  langBtn.id = 'langToggle';
  langBtn.className = 'lang-toggle';
  langBtn.textContent = lang === 'fr' ? 'EN' : 'FR';
  langBtn.addEventListener('click', () => {
    toggleLang();
    window.location.reload();
  });

  const adminLink = document.createElement('a');
  adminLink.href = '#/admin';
  adminLink.className = 'admin-link';
  adminLink.textContent = 'Admin';

  document.body.appendChild(footer);
  document.body.appendChild(langBtn);
  document.body.appendChild(adminLink);

  updateClock();
  setInterval(updateClock, 1000);
}

function updateClock() {
  const clockEl = document.getElementById('clock');
  if (clockEl) {
    clockEl.textContent = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  }
}

registerRoute('/', () => renderHome());
registerRoute('/checkin', () => renderCheckin());
registerRoute('/checkout', () => renderCheckout());
registerRoute('/confirmation', () => renderConfirmation());
registerRoute('/confirm-action', () => renderConfirmAction());
registerRoute('/admin', () => renderAdmin());

renderLayout();
startRouter();
