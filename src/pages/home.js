import QRCode from 'qrcode';

export function renderHome() {
  const app = document.getElementById('app');
  app.innerHTML = `
    <div class="kiosk-layout">
      <div class="kiosk-content">
        <div class="home-container animate-fade-in">

          <div class="home-header" style="text-align:center; margin-bottom:40px;">
            <div class="sap-logo-large">SAP</div>
            <h1 class="home-title">Bienvenue chez SAP</h1>
            <p class="home-subtitle">Service Aviation Paris — Veuillez sélectionner votre action</p>
          </div>

          <div class="home-main-row">
            <div class="home-left">
              <div class="tiles-grid">
                <a href="#/checkin" class="tile tile-arrival">
                  <svg class="tile-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                  </svg>
                  <span class="tile-title">Arrivée</span>
                  <span class="tile-subtitle">S'enregistrer</span>
                </a>

                <a href="#/checkout" class="tile tile-departure">
                  <svg class="tile-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                  <span class="tile-title">Départ</span>
                  <span class="tile-subtitle">Se déconnecter</span>
                </a>
              </div>

              <div class="home-help" style="text-align:center;">
                <p>Besoin d'aide ? Contactez la réception</p>
              </div>
            </div>

            <div class="home-qr-panel">
              <p class="home-qr-title">Enregistrez-vous sur mobile</p>
              <p class="home-qr-hint">Scannez ce code avec votre téléphone pour accéder au formulaire directement</p>
              <div class="home-qr-canvas-wrap">
                <canvas id="homeQrCanvas"></canvas>
              </div>
              <p class="home-qr-url" id="homeQrUrl"></p>
            </div>
          </div>

        </div>
      </div>
    </div>
  `;

  setTimeout(() => generateHomeQR(), 100);
}

async function generateHomeQR() {
  const canvas = document.getElementById('homeQrCanvas');
  const urlEl = document.getElementById('homeQrUrl');
  if (!canvas) return;

  const appUrl = window.location.origin + window.location.pathname;

  if (urlEl) {
    urlEl.textContent = appUrl;
  }

  try {
    await QRCode.toCanvas(canvas, appUrl, {
      width: 200,
      margin: 1,
      color: {
        dark: '#1140A9',
        light: '#ffffff',
      },
    });
  } catch (err) {
    console.error('QR generation error:', err);
  }
}
