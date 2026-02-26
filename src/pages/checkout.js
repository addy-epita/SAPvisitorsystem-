import { t } from '../i18n.js';
import { navigate } from '../router.js';
import { supabase } from '../supabase.js';
import { Html5Qrcode } from 'html5-qrcode';

export function renderCheckout() {
  const app = document.getElementById('app');
  app.innerHTML = `
    <div class="checkout-layout">
      <div class="checkout-container">
        <header class="checkout-header">
          <div class="checkout-header-left">
            <div class="checkout-logo">
              <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
              </svg>
            </div>
            <div>
              <h1 class="checkout-title-text">${t('checkout')}</h1>
              <p class="checkout-subtitle-text">SAP Visitor Management</p>
            </div>
          </div>
          <a href="#/" class="checkout-back-btn">${t('back')}</a>
        </header>

        <main class="checkout-main">
          <div class="glass-panel">
            <h2 class="glass-panel-title">
              <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
              </svg>
              ${t('scanQr')}
            </h2>
            <p class="glass-panel-hint">${t('helpText')}</p>

            <div id="qrScanner" class="qr-scanner-container"></div>

            <div class="scanner-controls">
              <button id="startScanBtn" class="btn-scanner-control">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                ${t('startScanner')}
              </button>
              <button id="stopScanBtn" class="btn-scanner-control hidden">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"></path>
                </svg>
                ${t('stopScanner')}
              </button>
            </div>

            <div id="scannerStatus" class="scanner-status hidden">
              <div class="spinner-blue"></div>
              <span>${t('processing')}</span>
            </div>
          </div>

          <div class="checkout-divider">
            <div class="divider-line"></div>
            <span class="divider-text">${t('orManual')}</span>
            <div class="divider-line"></div>
          </div>

          <div class="glass-panel">
            <form id="manualCheckoutForm" class="checkout-form">
              <div class="form-row-2">
                <div class="form-group">
                  <label>${t('firstName')}</label>
                  <input type="text" name="first_name" required class="checkout-input" placeholder="${t('firstName')}">
                </div>
                <div class="form-group">
                  <label>${t('lastName')}</label>
                  <input type="text" name="last_name" required class="checkout-input" placeholder="${t('lastName')}">
                </div>
              </div>
              <div class="form-group">
                <label>${t('company')}</label>
                <input type="text" name="company" required class="checkout-input" placeholder="${t('company')}">
              </div>
              <button type="submit" class="btn-checkout-primary" id="checkoutBtn">
                ${t('findVisit')}
              </button>
            </form>
          </div>
        </main>

        <footer class="checkout-footer">
          <p>SAP Visitor Management System</p>
        </footer>
      </div>
    </div>
  `;

  let html5QrCode = null;
  let isScanning = false;

  const startScanBtn = document.getElementById('startScanBtn');
  const stopScanBtn = document.getElementById('stopScanBtn');
  const scannerStatus = document.getElementById('scannerStatus');

  startScanBtn.addEventListener('click', async () => {
    if (isScanning) return;

    try {
      html5QrCode = new Html5Qrcode('qrScanner');

      await html5QrCode.start(
        { facingMode: 'environment' },
        {
          fps: 10,
          qrbox: { width: 250, height: 250 }
        },
        async (decodedText) => {
          if (isScanning) return;
          isScanning = true;

          scannerStatus.classList.remove('hidden');
          scannerStatus.innerHTML = `
            <div class="spinner-blue"></div>
            <span>${t('qrDetected')}</span>
          `;

          await html5QrCode.stop();
          stopScanBtn.classList.add('hidden');
          startScanBtn.classList.remove('hidden');

          await handleQRCheckout(decodedText);
        }
      );

      startScanBtn.classList.add('hidden');
      stopScanBtn.classList.remove('hidden');
    } catch (error) {
      console.error('Scanner error:', error);
      alert(t('cameraError'));
    }
  });

  stopScanBtn.addEventListener('click', async () => {
    if (html5QrCode) {
      await html5QrCode.stop();
      stopScanBtn.classList.add('hidden');
      startScanBtn.classList.remove('hidden');
      scannerStatus.classList.add('hidden');
      isScanning = false;
    }
  });

  async function handleQRCheckout(qrToken) {
    const { data: visitor, error } = await supabase
      .from('visitors')
      .select('id')
      .eq('qr_token', qrToken)
      .eq('status', 'checked_in')
      .maybeSingle();

    if (error || !visitor) {
      scannerStatus.innerHTML = `<span style="color: #ef4444;">${t('visitorNotFound')}</span>`;
      setTimeout(() => {
        scannerStatus.classList.add('hidden');
        isScanning = false;
      }, 3000);
      return;
    }

    const { error: updateError } = await supabase
      .from('visitors')
      .update({
        status: 'checked_out',
        departure_time: new Date().toISOString(),
        checkout_method: 'qr_scan',
      })
      .eq('id', visitor.id);

    if (updateError) {
      scannerStatus.innerHTML = `<span style="color: #ef4444;">${t('errorMessage')}</span>`;
      setTimeout(() => {
        scannerStatus.classList.add('hidden');
        isScanning = false;
      }, 3000);
      return;
    }

    navigate('/confirmation?type=checkout');
  }

  document.getElementById('manualCheckoutForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('checkoutBtn');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner"></span> ${t('processing')}`;

    const form = e.target;
    const firstName = form.first_name.value.trim();
    const lastName = form.last_name.value.trim();
    const company = form.company.value.trim();

    const { data: visitor, error } = await supabase
      .from('visitors')
      .select('id')
      .ilike('first_name', firstName)
      .ilike('last_name', lastName)
      .ilike('company', company)
      .eq('status', 'checked_in')
      .order('arrival_time', { ascending: false })
      .limit(1)
      .maybeSingle();

    if (error || !visitor) {
      alert(t('visitorNotFound'));
      btn.disabled = false;
      btn.textContent = originalText;
      return;
    }

    const { error: updateError } = await supabase
      .from('visitors')
      .update({
        status: 'checked_out',
        departure_time: new Date().toISOString(),
        checkout_method: 'manual_admin',
      })
      .eq('id', visitor.id);

    if (updateError) {
      alert(t('errorMessage'));
      btn.disabled = false;
      btn.textContent = originalText;
      return;
    }

    navigate('/confirmation?type=checkout');
  });
}
