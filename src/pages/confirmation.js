import { getRouteParams } from '../router.js';
import { supabase } from '../supabase.js';
import QRCode from 'qrcode';

function formatDuration(minutes) {
  const hours = Math.floor(minutes / 60);
  const mins = minutes % 60;
  if (hours > 0 && mins > 0) return `${hours}h ${mins}min`;
  if (hours > 0) return `${hours}h`;
  return `${mins}min`;
}

export async function renderConfirmation() {
  const params = getRouteParams();
  const type = params.type || 'checkin';
  const visitorId = params.id;

  let visitor = null;
  if (visitorId && type === 'checkin') {
    const { data } = await supabase
      .from('visitors')
      .select('first_name, last_name, company, phone, qr_token, arrival_time, expected_duration')
      .eq('id', visitorId)
      .maybeSingle();
    visitor = data;
  }

  const app = document.getElementById('app');

  if (type === 'checkout') {
    app.innerHTML = `
      <div class="confirmation-layout">
        <div class="confirmation-container animate-fade-in">
          <div class="confirmation-icon-wrap confirmation-icon-blue">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
          </div>
          <h1 class="confirmation-title">Au Revoir !</h1>
          <p class="confirmation-subtitle">Merci pour votre visite</p>

          <div class="confirmation-message-box">
            <p class="confirmation-message">Votre départ a été enregistré avec succès.</p>
            <p class="confirmation-sub">Bonne journée et bon retour !</p>
          </div>

          <a href="#/" class="btn-confirmation-primary">Retour à l'accueil</a>
        </div>
      </div>
    `;
    startAutoRedirect();
    return;
  }

  if (visitor) {
    const arrivalTime = new Date(visitor.arrival_time).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    const arrivalDate = new Date(visitor.arrival_time).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });

    app.innerHTML = `
      <div class="confirmation-layout">
        <div class="confirmation-container animate-fade-in">
          <div class="confirmation-icon-wrap confirmation-icon-green">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          <h1 class="confirmation-title">Enregistrement Réussi</h1>
          <p class="confirmation-subtitle">Bienvenue chez SAP — Service Aviation Paris</p>

          <div class="visitor-info-card">
            <h3 class="visitor-info-heading">Informations visiteur</h3>
            <div class="info-row">
              <span class="info-label">Nom</span>
              <span class="info-value">${visitor.first_name} ${visitor.last_name}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Société</span>
              <span class="info-value">${visitor.company}</span>
            </div>
            ${visitor.phone ? `
            <div class="info-row">
              <span class="info-label">Téléphone</span>
              <span class="info-value">${visitor.phone}</span>
            </div>` : ''}
            <div class="info-row">
              <span class="info-label">Arrivée</span>
              <span class="info-value">${arrivalDate} à ${arrivalTime}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Durée prévue</span>
              <span class="info-value">${formatDuration(visitor.expected_duration)}</span>
            </div>
          </div>

          <div class="qr-code-card">
            <h3 class="qr-code-heading">Votre QR Code de Visite</h3>
            <p class="qr-code-instruction">Conservez ce QR code — il vous permettra de vous déconnecter plus rapidement</p>
            <div class="qr-code-wrapper">
              <canvas id="qrCanvas"></canvas>
            </div>
            <button id="downloadQR" class="btn-qr-download">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
              </svg>
              Télécharger le QR Code
            </button>
          </div>

          <a href="#/" class="btn-confirmation-primary">Retour à l'accueil</a>
        </div>
      </div>
    `;

    setTimeout(() => generateQRCode(visitor.qr_token), 100);
  } else {
    app.innerHTML = `
      <div class="confirmation-layout">
        <div class="confirmation-container animate-fade-in">
          <div class="confirmation-icon-wrap confirmation-icon-green">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          <h1 class="confirmation-title">Enregistrement Réussi</h1>
          <p class="confirmation-subtitle">Bienvenue chez SAP</p>
          <a href="#/" class="btn-confirmation-primary">Retour à l'accueil</a>
        </div>
      </div>
    `;
  }

  startAutoRedirect();
}

function startAutoRedirect() {
  const timer = setTimeout(() => {
    window.location.hash = '/';
  }, 30000);
  window.addEventListener('hashchange', () => clearTimeout(timer), { once: true });
}

async function generateQRCode(token) {
  const canvas = document.getElementById('qrCanvas');
  if (!canvas || !token) return;

  try {
    await QRCode.toCanvas(canvas, token, {
      width: 240,
      margin: 2,
      color: {
        dark: '#1140A9',
        light: '#ffffff',
      },
    });

    const downloadBtn = document.getElementById('downloadQR');
    if (downloadBtn) {
      downloadBtn.addEventListener('click', () => {
        const dataUrl = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = `qr-visite-${token.substring(0, 8)}.png`;
        link.href = dataUrl;
        link.click();
      });
    }
  } catch (error) {
    console.error('QR generation error:', error);
  }
}
