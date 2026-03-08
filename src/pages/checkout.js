import { navigate } from '../router.js';
import { supabase } from '../supabase.js';

function formatTime(iso) {
  return new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

function todayISO() {
  return new Date().toISOString().slice(0, 10);
}

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
              <h1 class="checkout-title-text">Départ Visiteur</h1>
              <p class="checkout-subtitle-text">Service Aviation Paris</p>
            </div>
          </div>
          <a href="#/" class="checkout-back-btn">Retour</a>
        </header>

        <main class="checkout-main">
          <div class="glass-panel">
            <h2 class="glass-panel-title">
              <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
              Rechercher votre visite
            </h2>
            <p class="glass-panel-hint">Saisissez votre nom de famille et la date de votre arrivée</p>

            <form id="checkoutSearchForm" class="checkout-form" style="margin-top:20px;">
              <div class="form-group">
                <label class="checkout-form-label">Nom de famille *</label>
                <input type="text" name="last_name" required class="checkout-input" placeholder="Votre nom de famille" autocomplete="family-name">
              </div>
              <div class="form-group">
                <label class="checkout-form-label">Date d'arrivée *</label>
                <input type="date" name="arrival_date" required class="checkout-input" value="${todayISO()}" max="${todayISO()}">
              </div>
              <button type="submit" class="btn-checkout-primary" id="searchBtn">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Rechercher
              </button>
            </form>

            <div id="searchResults" class="hidden" style="margin-top:24px;"></div>
          </div>
        </main>

        <footer class="checkout-footer">
          <p>SAP — Service Aviation Paris</p>
        </footer>
      </div>
    </div>
  `;

  document.getElementById('checkoutSearchForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('searchBtn');
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner"></span> Recherche...`;

    const form = e.target;
    const lastName = form.last_name.value.trim();
    const arrivalDate = form.arrival_date.value;

    const dateStart = `${arrivalDate}T00:00:00.000Z`;
    const dateEnd = `${arrivalDate}T23:59:59.999Z`;

    const { data: visitors, error } = await supabase
      .from('visitors')
      .select('id, first_name, last_name, company, phone, arrival_time, expected_duration, host_email, host_name')
      .ilike('last_name', lastName)
      .gte('arrival_time', dateStart)
      .lte('arrival_time', dateEnd)
      .eq('status', 'checked_in')
      .order('arrival_time', { ascending: false });

    btn.disabled = false;
    btn.innerHTML = `<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg> Rechercher`;

    const resultsEl = document.getElementById('searchResults');
    resultsEl.classList.remove('hidden');

    if (error || !visitors || visitors.length === 0) {
      resultsEl.innerHTML = `
        <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:16px; color:#991b1b; text-align:center;">
          <strong>Aucune visite active trouvée.</strong><br>
          <span style="font-size:0.9rem;">Vérifiez vos informations ou contactez la réception.</span>
        </div>
      `;
      return;
    }

    if (visitors.length === 1) {
      renderMatchConfirm(resultsEl, visitors[0]);
    } else {
      renderMatchList(resultsEl, visitors);
    }
  });
}

function renderMatchConfirm(container, visitor) {
  const arrivalFormatted = new Date(visitor.arrival_time).toLocaleDateString('fr-FR', {
    weekday: 'long', day: 'numeric', month: 'long',
  }) + ' à ' + formatTime(visitor.arrival_time);

  container.innerHTML = `
    <div style="background:#f0f9ff; border:2px solid #1140A9; border-radius:8px; padding:20px;">
      <p style="font-weight:700; color:#1140A9; margin-bottom:12px;">Visite trouvée — confirmez votre départ</p>
      <div class="visit-match-item" style="background:white; margin-bottom:16px;">
        <div class="visit-match-info">
          <div class="visit-match-name">${visitor.first_name} ${visitor.last_name}</div>
          <div class="visit-match-details">${visitor.company} · Arrivée ${arrivalFormatted}</div>
          ${visitor.host_name ? `<div class="visit-match-details">Hôte : ${visitor.host_name}</div>` : ''}
        </div>
      </div>
      <button id="confirmDepartureBtn" class="btn-checkout-primary" data-visitor-id="${visitor.id}" data-host-email="${visitor.host_email}" data-host-name="${visitor.host_name || ''}" data-visitor-name="${visitor.first_name} ${visitor.last_name}" data-company="${visitor.company}">
        Confirmer le départ
      </button>
    </div>
  `;

  document.getElementById('confirmDepartureBtn').addEventListener('click', (ev) => {
    const btn = ev.currentTarget;
    performCheckout(btn.dataset.visitorId, btn.dataset.hostEmail, btn.dataset.hostName, btn.dataset.visitorName, btn.dataset.company, btn);
  });
}

function renderMatchList(container, visitors) {
  const items = visitors.map(v => {
    const arrivalFormatted = new Date(v.arrival_time).toLocaleDateString('fr-FR', {
      day: 'numeric', month: 'long',
    }) + ' à ' + formatTime(v.arrival_time);

    return `
      <div class="visit-match-item">
        <div class="visit-match-info">
          <div class="visit-match-name">${v.first_name} ${v.last_name}</div>
          <div class="visit-match-details">${v.company} · Arrivée ${arrivalFormatted}</div>
          ${v.host_name ? `<div class="visit-match-details">Hôte : ${v.host_name}</div>` : ''}
        </div>
        <button class="btn-confirm-checkout" data-visitor-id="${v.id}" data-host-email="${v.host_email}" data-host-name="${v.host_name || ''}" data-visitor-name="${v.first_name} ${v.last_name}" data-company="${v.company}">
          Confirmer le départ
        </button>
      </div>
    `;
  }).join('');

  container.innerHTML = `
    <p style="font-weight:700; color:#1140A9; margin-bottom:12px;">Plusieurs visites trouvées — sélectionnez la vôtre</p>
    <div class="visit-match-list">${items}</div>
  `;

  container.querySelectorAll('.btn-confirm-checkout').forEach(btn => {
    btn.addEventListener('click', (ev) => {
      const b = ev.currentTarget;
      performCheckout(b.dataset.visitorId, b.dataset.hostEmail, b.dataset.hostName, b.dataset.visitorName, b.dataset.company, b);
    });
  });
}

async function performCheckout(visitorId, hostEmail, hostName, visitorName, company, btn) {
  btn.disabled = true;
  btn.innerHTML = `<span class="spinner"></span>`;

  const { error } = await supabase
    .from('visitors')
    .update({
      status: 'checked_out',
      departure_time: new Date().toISOString(),
      checkout_method: 'self_checkout',
      updated_at: new Date().toISOString(),
    })
    .eq('id', visitorId);

  if (error) {
    btn.disabled = false;
    btn.textContent = 'Confirmer le départ';
    alert('Une erreur s\'est produite. Veuillez réessayer.');
    return;
  }

  const supabaseUrl = import.meta.env.VITE_SUPABASE_URL;
  const supabaseKey = import.meta.env.VITE_SUPABASE_ANON_KEY;

  fetch(`${supabaseUrl}/functions/v1/send-notification`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${supabaseKey}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      visitor_id: parseInt(visitorId),
      type: 'checkout',
      visitor_name: visitorName,
      company,
      host_email: hostEmail,
      host_name: hostName || hostEmail,
      arrival_time: new Date().toISOString(),
      departure_time: new Date().toISOString(),
    }),
  }).catch(err => console.error('Checkout notification error:', err));

  navigate('/confirmation?type=checkout');
}
