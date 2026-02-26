import { t } from '../i18n.js';
import { navigate } from '../router.js';

export function renderHome() {
  const app = document.getElementById('app');
  app.innerHTML = `
    <div class="kiosk-layout">
      <div class="kiosk-content">
        <div class="home-container">
          <div class="home-header animate-fade-in">
            <div class="sap-logo-large">SAP</div>
            <h1 class="home-title">${t('welcome')}</h1>
            <p class="home-subtitle">${t('instruction')}</p>
          </div>

          <div class="tiles-grid">
            <a href="#/checkin" class="tile tile-arrival">
              <svg class="tile-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
              </svg>
              <span class="tile-title">${t('arrival')}</span>
              <span class="tile-subtitle">${t('checkin')}</span>
            </a>

            <a href="#/checkout" class="tile tile-departure">
              <svg class="tile-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              <span class="tile-title">${t('departure')}</span>
              <span class="tile-subtitle">${t('checkout')}</span>
            </a>
          </div>

          <div class="home-help">
            <p>${t('needHelp')}</p>
            <p class="help-sub">${t('contactReception')}</p>
          </div>
        </div>
      </div>
    </div>
  `;
}
