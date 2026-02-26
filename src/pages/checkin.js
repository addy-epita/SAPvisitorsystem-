import { t } from '../i18n.js';
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
    { value: 120, label: t('hours2') },
    { value: 180, label: t('hours3') },
    { value: 240, label: t('hours4') },
    { value: 360, label: t('hours6') },
    { value: 480, label: t('hours8') },
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
            <h1 class="checkin-title">${t('visitorCheckin')}</h1>
            <p class="checkin-subtitle">${t('fillForm')}</p>
          </div>

          <form id="checkinForm" class="checkin-form">
            <div class="form-row-2">
              <div class="form-group">
                <label for="firstName">${t('firstName')}</label>
                <input type="text" id="firstName" name="first_name" required minlength="2" placeholder="${t('firstName')}" autocomplete="given-name">
              </div>
              <div class="form-group">
                <label for="lastName">${t('lastName')}</label>
                <input type="text" id="lastName" name="last_name" required minlength="2" placeholder="${t('lastName')}" autocomplete="family-name">
              </div>
            </div>

            <div class="form-group">
              <label for="company">${t('company')}</label>
              <input type="text" id="company" name="company" required minlength="2" placeholder="${t('company')}" autocomplete="organization">
            </div>

            <div class="form-group">
              <label for="reason">${t('reason')}</label>
              <input type="text" id="reason" name="reason" required minlength="3" placeholder="${t('reasonPlaceholder')}">
            </div>

            <div class="form-group">
              <label for="hostSelect">${t('host')}</label>
              <select id="hostSelect" name="host_select">
                <option value="">${t('selectHost')}</option>
                ${hostList.map(h => `<option value="${h.id}" data-email="${h.email}">${h.name} (${h.department})</option>`).join('')}
                <option value="other">${t('otherHost')}</option>
              </select>
            </div>

            <div class="form-group">
              <label for="hostEmail">${t('hostEmail')}</label>
              <input type="email" id="hostEmail" name="host_email" required placeholder="email@sap.com" autocomplete="email">
            </div>

            <div class="form-group">
              <label for="visitorEmail">${t('visitorEmail')}</label>
              <input type="email" id="visitorEmail" name="visitor_email" placeholder="votre@email.com" autocomplete="email">
            </div>

            <div class="form-group">
              <label for="duration">${t('duration')}</label>
              <select id="duration" name="expected_duration">
                ${durations.map(d => `<option value="${d.value}" ${d.value === 180 ? 'selected' : ''}>${d.label}</option>`).join('')}
              </select>
            </div>

            <div class="form-actions">
              <a href="#/" class="btn-secondary">${t('cancel')}</a>
              <button type="submit" class="btn-primary" id="submitBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                ${t('submit')}
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
    btn.innerHTML = `<span class="spinner"></span> ${t('processing')}`;

    const form = e.target;
    const qrToken = crypto.randomUUID().replace(/-/g, '').slice(0, 32);

    const selectedHost = hostSelect.selectedOptions[0];
    const hostName = selectedHost?.value && selectedHost.value !== 'other' && selectedHost.value !== ''
      ? selectedHost.textContent.split('(')[0].trim()
      : '';

    const { data, error } = await supabase.from('visitors').insert({
      first_name: form.first_name.value.trim(),
      last_name: form.last_name.value.trim(),
      company: form.company.value.trim(),
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
      btn.innerHTML = `${t('submit')}`;
      alert(t('errorMessage'));
      return;
    }

    // Generate action token for host confirmation
    const actionToken = crypto.randomUUID().replace(/-/g, '');
    const expiresAt = new Date();
    expiresAt.setHours(expiresAt.getHours() + 24);

    await supabase.from('action_tokens').insert({
      visitor_id: data.id,
      token: actionToken,
      action_type: 'confirm_present',
      expires_at: expiresAt.toISOString(),
    });

    // Send arrival notification email (fire and forget)
    try {
      const supabaseUrl = import.meta.env.VITE_SUPABASE_URL;
      const supabaseKey = import.meta.env.VITE_SUPABASE_ANON_KEY;

      fetch(`${supabaseUrl}/functions/v1/send-notification`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${supabaseKey}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          visitor_id: data.id,
          type: 'arrival',
          visitor_name: `${data.first_name} ${data.last_name}`,
          company: data.company,
          host_email: data.host_email,
          host_name: hostName || data.host_email,
          arrival_time: data.arrival_time,
          action_token: actionToken,
        }),
      }).catch(err => console.error('Email notification error:', err));
    } catch (err) {
      console.error('Failed to trigger notification:', err);
    }

    navigate(`/confirmation?type=checkin&id=${data.id}`);
  });
}
