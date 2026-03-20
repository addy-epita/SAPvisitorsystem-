import QRCode from 'qrcode';
import { isDemoMode, toggleDemoMode, updateDemoBanner } from '../demo.js';

const BANNER_IMAGES = [
  'https://sapformations.com/wp-content/uploads/2019/02/AVITAILLEMENT-7.jpg',
  'https://sapformations.com/wp-content/uploads/2018/06/Fiche-60_Avitailleur-avion.jpg',
  'https://sapformations.com/wp-content/uploads/2019/02/SAP-1.jpg',
  'https://sapformations.com/wp-content/uploads/2019/02/JIG.jpg',
];

export function renderHome() {
  const app = document.getElementById('app');
  app.innerHTML = `
    <div class="home-page">

      <div class="home-banner">
        <div class="home-banner-slides">
          ${BANNER_IMAGES.map((src, i) => `
            <div class="home-banner-slide ${i === 0 ? 'active' : ''}" style="background-image: url('${src}')"></div>
          `).join('')}
        </div>
        <div class="home-banner-overlay"></div>
        <div class="home-banner-content">
          <img src="/assets/images/logo_sap_def_fevrier.jpg" alt="Service Aviation Paris" class="home-banner-logo">
          <h1 class="home-banner-title">Gestion des Visiteurs</h1>
          <p class="home-banner-sub">Service Aviation Paris</p>
        </div>
        <div class="home-banner-dots">
          ${BANNER_IMAGES.map((_, i) => `<button class="banner-dot ${i === 0 ? 'active' : ''}" data-index="${i}"></button>`).join('')}
        </div>
      </div>

      <div class="home-body">

        <div class="home-actions">
          <p class="home-actions-label">Sélectionnez votre action</p>

          <a href="#/checkin" class="action-tile action-arrival">
            <div class="action-tile-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
              </svg>
            </div>
            <div class="action-tile-text">
              <span class="action-tile-title">Arrivée</span>
              <span class="action-tile-desc">Enregistrer votre visite</span>
            </div>
            <div class="action-tile-arrow">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </div>
          </a>

          <a href="#/checkout" class="action-tile action-departure">
            <div class="action-tile-icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </div>
            <div class="action-tile-text">
              <span class="action-tile-title">Départ</span>
              <span class="action-tile-desc">Enregistrer votre départ</span>
            </div>
            <div class="action-tile-arrow">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </div>
          </a>

          <p class="home-help-text">Besoin d'aide ? Contactez la réception</p>

          <div class="home-util-row">
            <a href="#/flow" class="home-util-btn home-util-flow">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
              </svg>
              Voir le processus
            </a>
            <button class="home-util-btn home-util-demo" id="demoToggleBtn">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
              </svg>
              <span id="demoToggleLabel">${isDemoMode() ? 'Désactiver démo' : 'Mode démo'}</span>
            </button>
          </div>

        </div>

        <div class="home-qr-side">
          <div class="home-qr-card">
            <div class="home-qr-card-header">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="home-qr-header-icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
              <div>
                <p class="home-qr-card-title">Enregistrement mobile</p>
                <p class="home-qr-card-sub">Scannez avec votre téléphone</p>
              </div>
            </div>
            <div class="home-qr-frame">
              <div class="qr-corner qr-corner-tl"></div>
              <div class="qr-corner qr-corner-tr"></div>
              <div class="qr-corner qr-corner-bl"></div>
              <div class="qr-corner qr-corner-br"></div>
              <canvas id="homeQrCanvas"></canvas>
            </div>
            <p class="home-qr-instruction">Pointez votre appareil photo vers ce code pour accéder au formulaire d'enregistrement</p>
            <p class="home-qr-url" id="homeQrUrl"></p>
          </div>
        </div>

      </div>

    </div>
  `;

  setTimeout(() => {
    generateHomeQR();
    startBannerSlideshow();

    const demoBtn = document.getElementById('demoToggleBtn');
    if (demoBtn) {
      demoBtn.addEventListener('click', () => {
        toggleDemoMode();
        const label = document.getElementById('demoToggleLabel');
        if (label) label.textContent = isDemoMode() ? 'Désactiver démo' : 'Mode démo';
        demoBtn.classList.toggle('home-util-demo-active', isDemoMode());
      });
      demoBtn.classList.toggle('home-util-demo-active', isDemoMode());
    }
  }, 80);
}

async function generateHomeQR() {
  const canvas = document.getElementById('homeQrCanvas');
  const urlEl = document.getElementById('homeQrUrl');
  if (!canvas) return;

  const appUrl = window.location.origin + window.location.pathname;
  if (urlEl) urlEl.textContent = appUrl;

  try {
    await QRCode.toCanvas(canvas, appUrl, {
      width: 220,
      margin: 1,
      color: { dark: '#0d2f85', light: '#ffffff' },
    });
  } catch (err) {
    console.error('QR generation error:', err);
  }
}

function startBannerSlideshow() {
  const slides = document.querySelectorAll('.home-banner-slide');
  const dots = document.querySelectorAll('.banner-dot');
  if (slides.length < 2) return;

  let current = 0;

  dots.forEach((dot) => {
    dot.addEventListener('click', () => {
      goTo(parseInt(dot.dataset.index));
    });
  });

  function goTo(index) {
    slides[current].classList.remove('active');
    dots[current]?.classList.remove('active');
    current = index;
    slides[current].classList.add('active');
    dots[current]?.classList.add('active');
  }

  setInterval(() => {
    goTo((current + 1) % slides.length);
  }, 4000);
}

