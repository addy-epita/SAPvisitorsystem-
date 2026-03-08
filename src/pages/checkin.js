import { navigate } from '../router.js';
import { supabase } from '../supabase.js';

export async function renderCheckin() {
  const { data: hosts } = await supabase
    .from('hosts')
    .select('id, name, email, department')
    .eq('is_active', true)
    .order('name');

  const hostList = hosts || [];

  const durations = [
    { value: 120, label: '2 heures' },
    { value: 240, label: '4 heures' },
    { value: 480, label: '8 heures' },
  ];

  const app = document.getElementById('app');
  app.innerHTML = `
    <div class="kiosk-layout">
      <div class="kiosk-content checkin-scroll">
        <div class="checkin-container animate-fade-in">
          <div class="checkin-header">
            <div class="checkin-icon-wrap">
              <svg xmlns="http://www.w3.org/2000/svg" class="checkin-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
              </svg>
            </div>
            <h1 class="checkin-title">Enregistrement Visiteur</h1>
            <p class="checkin-subtitle">Veuillez remplir le formulaire ci-dessous</p>
          </div>

          <form id="checkinForm" class="checkin-form">
            <div class="form-row-2">
              <div class="form-group">
                <label for="firstName">Prénom *</label>
                <input type="text" id="firstName" name="first_name" required minlength="2" placeholder="Prénom" autocomplete="given-name">
              </div>
              <div class="form-group">
                <label for="lastName">Nom de famille *</label>
                <input type="text" id="lastName" name="last_name" required minlength="2" placeholder="Nom" autocomplete="family-name">
              </div>
            </div>

            <div class="form-row-2">
              <div class="form-group">
                <label for="company">Société *</label>
                <input type="text" id="company" name="company" required minlength="2" placeholder="Société" autocomplete="organization">
              </div>
              <div class="form-group">
                <label for="phone">Téléphone *</label>
                <input type="tel" id="phone" name="phone" required placeholder="+33 6 00 00 00 00" autocomplete="tel">
              </div>
            </div>

            <div class="form-group">
              <label for="reason">Motif de la visite *</label>
              <input type="text" id="reason" name="reason" required minlength="3" placeholder="Ex : Réunion, Maintenance, Livraison...">
            </div>

            <div class="form-row-2">
              <div class="form-group">
                <label for="hostSelect">Hôte *</label>
                <select id="hostSelect" name="host_select">
                  <option value="">Sélectionnez un hôte...</option>
                  ${hostList.map(h => `<option value="${h.id}" data-email="${h.email}" data-name="${h.name}">${h.name} (${h.department})</option>`).join('')}
                  <option value="other">Autre (saisir email)</option>
                </select>
              </div>
              <div class="form-group">
                <label for="hostEmail">Email de l'hôte *</label>
                <input type="email" id="hostEmail" name="host_email" required placeholder="email@sapformations.com" autocomplete="email">
              </div>
            </div>

            <div class="form-group">
              <label for="visitorEmail">Email visiteur (optionnel)</label>
              <input type="email" id="visitorEmail" name="visitor_email" placeholder="votre@email.com" autocomplete="email">
            </div>

            <div class="form-group">
              <label for="duration">Durée prévue *</label>
              <select id="duration" name="expected_duration">
                ${durations.map(d => `<option value="${d.value}" ${d.value === 120 ? 'selected' : ''}>${d.label}</option>`).join('')}
              </select>
            </div>

            <div class="form-actions">
              <a href="#/" class="btn-secondary">Annuler</a>
              <button type="submit" class="btn-primary" id="submitBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Confirmer l'arrivée
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  `;

  const hostSelect = document.getElementById('hostSelect');
  const hostEmailInput = document.getElementById('hostEmail');

  hostSelect.addEventListener('change', () => {
    const selected = hostSelect.selectedOptions[0];
    if (selected && selected.dataset.email) {
      hostEmailInput.value = selected.dataset.email;
      hostEmailInput.readOnly = true;
    } else {
      hostEmailInput.value = '';
      hostEmailInput.readOnly = false;
    }
  });

  document.getElementById('checkinForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner"></span> Traitement en cours...`;

    const form = e.target;
    const qrToken = crypto.randomUUID().replace(/-/g, '').slice(0, 32);

    const selectedHost = hostSelect.selectedOptions[0];
    const hostName = selectedHost?.value && selectedHost.value !== 'other' && selectedHost.value !== ''
      ? (selectedHost.dataset.name || selectedHost.textContent.split('(')[0].trim())
      : '';

    const { data, error } = await supabase.from('visitors').insert({
      first_name: form.first_name.value.trim(),
      last_name: form.last_name.value.trim(),
      company: form.company.value.trim(),
      phone: form.phone.value.trim(),
      reason: form.reason.value.trim(),
      host_email: form.host_email.value.trim(),
      host_name: hostName,
      visitor_email: form.visitor_email.value.trim() || null,
      arrival_time: new Date().toISOString(),
      expected_duration: parseInt(form.expected_duration.value),
      status: 'checked_in',
      checkin_method: 'kiosk',
      qr_token: qrToken,
    }).select().maybeSingle();

    if (error) {
      btn.disabled = false;
      btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg> Confirmer l'arrivée`;
      alert('Une erreur s\'est produite. Veuillez réessayer.');
      return;
    }

    const actionToken = crypto.randomUUID().replace(/-/g, '');
    const expiresAt = new Date();
    expiresAt.setHours(expiresAt.getHours() + 24);

    await supabase.from('action_tokens').insert({
      visitor_id: data.id,
      token: actionToken,
      action_type: 'confirm_present',
      expires_at: expiresAt.toISOString(),
    });

    const supabaseUrl = import.meta.env.VITE_SUPABASE_URL;
    const supabaseKey = import.meta.env.VITE_SUPABASE_ANON_KEY;

    const notifPayload = {
      visitor_id: data.id,
      type: 'arrival',
      visitor_name: `${data.first_name} ${data.last_name}`,
      company: data.company,
      phone: data.phone,
      host_email: data.host_email,
      host_name: hostName || data.host_email,
      arrival_time: data.arrival_time,
      expected_duration: data.expected_duration,
      action_token: actionToken,
    };

    fetch(`${supabaseUrl}/functions/v1/send-notification`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${supabaseKey}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(notifPayload),
    }).catch(err => console.error('Email notification error:', err));

    navigate(`/confirmation?type=checkin&id=${data.id}`);
  });
}
