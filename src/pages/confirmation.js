import { t } from '../i18n.js';
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
      .select('first_name, last_name, company, qr_token, arrival_time, expected_duration')
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
          <h1 class="confirmation-title">${t('checkoutTitle')}</h1>
          <p class="confirmation-subtitle">${t('checkoutSubtitle')}</p>

          <div class="confirmation-message-box">
            <p class="confirmation-message">${t('checkoutMessage')}</p>
            <p class="confirmation-sub">${t('safeTravels')}</p>
          </div>

          <a href="#/" class="btn-confirmation-primary">${t('backHome')}</a>
        </div>
      </div>
    `;
    startAutoRedirect();
    return;
  }

  if (visitor) {
    const arrivalTime = new Date(visitor.arrival_time).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    app.innerHTML = `
      <div class="confirmation-layout">
        <div class="confirmation-container animate-fade-in">
          <div class="confirmation-icon-wrap confirmation-icon-green">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          <h1 class="confirmation-title">${t('checkinTitle')}</h1>
          <p class="confirmation-subtitle">${t('checkinSubtitle')}</p>

          <div class="visitor-info-card">
            <h3 class="visitor-info-heading">${t('visitorInfo')}</h3>
            <div class="info-row">
              <span class="info-label">${t('name')}</span>
              <span class="info-value">${visitor.first_name} ${visitor.last_name}</span>
            </div>
            <div class="info-row">
              <span class="info-label">${t('company')}</span>
              <span class="info-value">${visitor.company}</span>
            </div>
            <div class="info-row">
              <span class="info-label">${t('arrivalTime')}</span>
              <span class="info-value">${arrivalTime}</span>
            </div>
            <div class="info-row">
              <span class="info-label">${t('expectedDuration')}</span>
              <span class="info-value">${formatDuration(visitor.expected_duration)}</span>
            </div>
          </div>

          <div class="qr-code-card">
            <h3 class="qr-code-heading">${t('yourCheckoutQR')}</h3>
            <p class="qr-code-instruction">${t('qrInstruction')}</p>
            <div class="qr-code-wrapper">
              <canvas id="qrCanvas"></canvas>
            </div>
            <button id="downloadQR" class="btn-qr-download">
              <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
              </svg>
              ${t('downloadQR')}
            </button>
          </div>

          <a href="#/" class="btn-confirmation-primary">${t('backHome')}</a>
        </div>
      </div>
    `;

    generateQRCode(visitor.qr_token);
  } else {
    app.innerHTML = `
      <div class="confirmation-layout">
        <div class="confirmation-container animate-fade-in">
          <div class="confirmation-icon-wrap confirmation-icon-green">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          <h1 class="confirmation-title">${t('checkinTitle')}</h1>
          <p class="confirmation-subtitle">${t('checkinSubtitle')}</p>
          <a href="#/" class="btn-confirmation-primary">${t('backHome')}</a>
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
  if (!canvas) return;

  try {
    await QRCode.toCanvas(canvas, token, {
      width: 280,
      margin: 2,
      color: {
        dark: '#1a1a1a',
        light: '#ffffff'
      }
    });

    const downloadBtn = document.getElementById('downloadQR');
    if (downloadBtn) {
      downloadBtn.addEventListener('click', () => {
        const dataUrl = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = `visitor-qr-${token.substring(0, 8)}.png`;
        link.href = dataUrl;
        link.click();
      });
    }
  } catch (error) {
    console.error('QR generation error:', error);
  }
}
