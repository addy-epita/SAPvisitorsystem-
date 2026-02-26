import { t } from '../i18n.js';
import { supabase } from '../supabase.js';
import QRCode from 'qrcode';

let adminSession = null;
let refreshInterval = null;
let currentView = 'dashboard';

function checkAdmin() {
  return sessionStorage.getItem('admin_logged_in') === 'true';
}

function renderLoginForm() {
  return `
    <div class="admin-login-layout">
      <div class="admin-login-card animate-fade-in">
        <div class="admin-login-header">
          <div class="admin-logo">SAP</div>
          <h1 class="admin-login-title">${t('adminLogin')}</h1>
        </div>
        <form id="adminLoginForm" class="admin-login-form">
          <div class="form-group">
            <label for="adminEmail">${t('email')}</label>
            <input type="email" id="adminEmail" required placeholder="admin@example.com" class="admin-input">
          </div>
          <div class="form-group">
            <label for="adminPassword">${t('password')}</label>
            <input type="password" id="adminPassword" required placeholder="********" class="admin-input">
          </div>
          <button type="submit" class="btn-admin-primary" id="loginBtn">${t('login')}</button>
          <div id="loginError" class="login-error hidden"></div>
        </form>
        <a href="#/" class="admin-back-link">${t('backHome')}</a>
      </div>
    </div>
  `;
}

export async function renderAdmin() {
  const app = document.getElementById('app');

  if (!checkAdmin()) {
    app.innerHTML = renderLoginForm();
    document.getElementById('adminLoginForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const email = document.getElementById('adminEmail').value;
      const password = document.getElementById('adminPassword').value;
      const errorEl = document.getElementById('loginError');
      const btn = document.getElementById('loginBtn');

      btn.disabled = true;
      btn.textContent = '...';

      const { data: admin } = await supabase
        .from('admin_users')
        .select('id, name, email, role, password_hash')
        .eq('email', email)
        .eq('is_active', true)
        .maybeSingle();

      if (!admin) {
        errorEl.textContent = 'Invalid credentials';
        errorEl.classList.remove('hidden');
        btn.disabled = false;
        btn.textContent = t('login');
        return;
      }

      sessionStorage.setItem('admin_logged_in', 'true');
      sessionStorage.setItem('admin_name', admin.name);
      sessionStorage.setItem('admin_email', admin.email);
      renderDashboard();
    });
    return;
  }

  renderDashboard();
}

async function renderDashboard() {
  const app = document.getElementById('app');
  const adminName = sessionStorage.getItem('admin_name') || 'Admin';

  // Clear any existing refresh interval
  if (refreshInterval) {
    clearInterval(refreshInterval);
    refreshInterval = null;
  }

  app.innerHTML = `
    <div class="admin-layout">
      <nav class="admin-nav">
        <div class="admin-nav-inner">
          <h1 class="admin-nav-title">SAP Visitor Management</h1>
          <div class="admin-nav-right">
            <span class="admin-nav-user">${adminName}</span>
            <button id="logoutBtn" class="admin-logout-btn">${t('logout')}</button>
          </div>
        </div>
      </nav>

      <div class="admin-body">
        <aside class="admin-sidebar">
          <nav class="admin-sidebar-nav">
            <button class="sidebar-link ${currentView === 'dashboard' ? 'active' : ''}" data-view="dashboard">
              <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
              </svg>
              ${t('dashboard')}
            </button>
            <button class="sidebar-link ${currentView === 'now' ? 'active' : ''}" data-view="now">
              <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
              </svg>
              ${t('onSiteNow')}
            </button>
            <button class="sidebar-link ${currentView === 'visitors' ? 'active' : ''}" data-view="visitors">
              <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
              </svg>
              All Visitors
            </button>
            <button class="sidebar-link ${currentView === 'qr-posters' ? 'active' : ''}" data-view="qr-posters">
              <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
              </svg>
              QR Posters
            </button>
            <button class="sidebar-link ${currentView === 'settings' ? 'active' : ''}" data-view="settings">
              <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              Settings
            </button>
          </nav>
        </aside>

        <main class="admin-main" id="adminMainContent">
          <div class="loading-spinner">Loading...</div>
        </main>
      </div>
    </div>

    <div id="modalOverlay" class="modal-overlay hidden"></div>
    <div id="manualCheckoutModal" class="admin-modal hidden">
      <div class="modal-content">
        <h3>Manual Checkout</h3>
        <p id="modalVisitorName"></p>
        <div class="form-group">
          <label>Reason</label>
          <select id="checkoutReason">
            <option value="forgot">Forgot to check out</option>
            <option value="no_show">No show</option>
            <option value="emergency">Emergency</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Notes (optional)</label>
          <textarea id="checkoutNotes" rows="3"></textarea>
        </div>
        <div class="modal-actions">
          <button id="cancelCheckout" class="btn-secondary">Cancel</button>
          <button id="confirmCheckout" class="btn-primary">Confirm Checkout</button>
        </div>
      </div>
    </div>
  `;

  document.getElementById('logoutBtn').addEventListener('click', () => {
    if (refreshInterval) clearInterval(refreshInterval);
    sessionStorage.clear();
    window.location.hash = '/';
  });

  document.querySelectorAll('.sidebar-link').forEach(link => {
    link.addEventListener('click', (e) => {
      const view = e.currentTarget.dataset.view;
      currentView = view;
      document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
      e.currentTarget.classList.add('active');
      renderView(view);
    });
  });

  // Render initial view
  renderView(currentView);
}

async function renderView(view) {
  const content = document.getElementById('adminMainContent');

  if (view === 'dashboard') {
    content.innerHTML = await renderDashboardView();
  } else if (view === 'now') {
    content.innerHTML = await renderNowView();
    startAutoRefresh();
  } else if (view === 'visitors') {
    content.innerHTML = await renderAllVisitorsView();
  } else if (view === 'qr-posters') {
    content.innerHTML = renderQRPostersView();
    setTimeout(() => generatePosterQR(), 100);
  } else if (view === 'settings') {
    content.innerHTML = await renderSettingsView();
    setupSettingsHandlers();
  }
}

async function renderSettingsView() {
  const { data: settings } = await supabase
    .from('settings')
    .select('*')
    .order('setting_key');

  const settingsMap = new Map(settings?.map(s => [s.setting_key, s.setting_value]));

  return `
    <div class="view-header">
      <div>
        <h2 class="view-title">Settings</h2>
        <p class="view-subtitle">Configure system behavior and notifications</p>
      </div>
    </div>

    <div class="settings-grid">
      <div class="settings-card">
        <h3 class="settings-card-title">Notification Settings</h3>
        <form id="settingsForm">
          <div class="form-group">
            <label for="emailEnabled">Enable Email Notifications</label>
            <select id="emailEnabled" name="enable_email_notifications">
              <option value="true" ${settingsMap.get('enable_email_notifications') === 'true' ? 'selected' : ''}>Enabled</option>
              <option value="false" ${settingsMap.get('enable_email_notifications') === 'false' ? 'selected' : ''}>Disabled</option>
            </select>
          </div>

          <div class="form-group">
            <label for="defaultDuration">Default Visit Duration (minutes)</label>
            <input type="number" id="defaultDuration" name="default_duration" value="${settingsMap.get('default_duration') || '180'}" min="30" max="480" step="30">
          </div>

          <div class="form-group">
            <label for="endOfDayTime">End of Day Time</label>
            <input type="time" id="endOfDayTime" name="end_of_day_time" value="${settingsMap.get('end_of_day_time') || '18:00'}">
          </div>

          <div class="form-group">
            <label for="reminderIntervals">Reminder Intervals (comma-separated minutes)</label>
            <input type="text" id="reminderIntervals" name="reminder_intervals" value="${(JSON.parse(settingsMap.get('reminder_intervals') || '[240]')).join(', ')}" placeholder="120, 240, 360, 480">
            <small class="form-hint">e.g., 120, 240 for 2-hour and 4-hour reminders</small>
          </div>

          <div class="form-group">
            <label for="supervisorEmails">Supervisor Emails (comma-separated)</label>
            <textarea id="supervisorEmails" name="supervisor_emails" rows="3" placeholder="supervisor1@sap.com, supervisor2@sap.com">${settingsMap.get('supervisor_emails') ? JSON.parse(settingsMap.get('supervisor_emails')).join(', ') : ''}</textarea>
            <small class="form-hint">These emails will receive end-of-day escalation reports</small>
          </div>

          <div class="form-group">
            <label for="retentionDays">Data Retention (days)</label>
            <input type="number" id="retentionDays" name="data_retention_days" value="${settingsMap.get('data_retention_days') || '365'}" min="30" max="3650">
            <small class="form-hint">Visitor data older than this will be anonymized</small>
          </div>

          <button type="submit" class="btn-primary" id="saveSettingsBtn">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Save Settings
          </button>
        </form>
      </div>

      <div class="settings-card">
        <h3 class="settings-card-title">System Information</h3>
        <div class="info-list">
          <div class="info-item">
            <span class="info-label">Site Name</span>
            <span class="info-value">${settingsMap.get('site_name') || 'SAP Office'}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Company</span>
            <span class="info-value">${settingsMap.get('company_name') || 'SAP'}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Timezone</span>
            <span class="info-value">${settingsMap.get('timezone') || 'Europe/Paris'}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Default Language</span>
            <span class="info-value">${settingsMap.get('language_default') === 'en' ? 'English' : 'Français'}</span>
          </div>
        </div>
      </div>
    </div>
  `;
}

function setupSettingsHandlers() {
  const form = document.getElementById('settingsForm');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('saveSettingsBtn');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Saving...';

    try {
      const formData = new FormData(form);

      const updates = [
        { key: 'enable_email_notifications', value: formData.get('enable_email_notifications'), type: 'boolean' },
        { key: 'default_duration', value: formData.get('default_duration'), type: 'integer' },
        { key: 'end_of_day_time', value: formData.get('end_of_day_time'), type: 'string' },
        { key: 'data_retention_days', value: formData.get('data_retention_days'), type: 'integer' },
      ];

      // Parse reminder intervals
      const reminderText = formData.get('reminder_intervals');
      const reminderIntervals = reminderText.split(',').map(s => parseInt(s.trim())).filter(n => !isNaN(n));
      updates.push({ key: 'reminder_intervals', value: JSON.stringify(reminderIntervals), type: 'json' });

      // Parse supervisor emails
      const supervisorText = formData.get('supervisor_emails');
      if (supervisorText && supervisorText.trim()) {
        const supervisorEmails = supervisorText.split(',').map(s => s.trim()).filter(s => s);
        updates.push({ key: 'supervisor_emails', value: JSON.stringify(supervisorEmails), type: 'array' });
      }

      for (const { key, value, type } of updates) {
        await supabase
          .from('settings')
          .update({
            setting_value: value,
            setting_type: type,
            updated_at: new Date().toISOString(),
            updated_by: sessionStorage.getItem('admin_email'),
          })
          .eq('setting_key', key);
      }

      // Log the action
      await supabase.from('audit_log').insert({
        action: 'settings_updated',
        user_email: sessionStorage.getItem('admin_email'),
        details: 'System settings updated',
      });

      btn.innerHTML = '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Saved!';
      setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = originalContent;
      }, 2000);
    } catch (error) {
      console.error('Settings save error:', error);
      alert('Failed to save settings');
      btn.disabled = false;
      btn.innerHTML = originalContent;
    }
  });
}

async function generatePosterQR() {
  const container = document.getElementById('posterQRCode');
  if (!container) return;

  const baseUrl = window.location.origin + window.location.pathname;
  const checkoutUrl = `${baseUrl}#/checkout`;

  const canvas = document.createElement('canvas');
  container.appendChild(canvas);

  try {
    await QRCode.toCanvas(canvas, checkoutUrl, {
      width: 300,
      margin: 2,
      color: {
        dark: '#1a1a1a',
        light: '#ffffff'
      }
    });
  } catch (error) {
    console.error('QR generation error:', error);
    container.innerHTML = '<p>Failed to generate QR code</p>';
  }
}

async function renderDashboardView() {
  const todayStart = new Date();
  todayStart.setHours(0, 0, 0, 0);
  const todayEnd = new Date();
  todayEnd.setHours(23, 59, 59, 999);

  const [todayResult, onSiteResult, weekResult, recentResult] = await Promise.all([
    supabase.from('visitors').select('id', { count: 'exact', head: true })
      .gte('arrival_time', todayStart.toISOString())
      .lte('arrival_time', todayEnd.toISOString()),
    supabase.from('visitors').select('id', { count: 'exact', head: true })
      .eq('status', 'checked_in'),
    supabase.from('visitors').select('id', { count: 'exact', head: true })
      .gte('arrival_time', getWeekStart().toISOString()),
    supabase.from('visitors').select('*')
      .gte('arrival_time', todayStart.toISOString())
      .lte('arrival_time', todayEnd.toISOString())
      .order('arrival_time', { ascending: false })
      .limit(10),
  ]);

  const visitorsToday = todayResult.count || 0;
  const currentlyOnSite = onSiteResult.count || 0;
  const weekVisitors = weekResult.count || 0;
  const recentVisitors = recentResult.data || [];

  return `
    <div class="stats-grid">
      <div class="stat-card">
        <p class="stat-label">${t('today')}</p>
        <p class="stat-value stat-blue">${visitorsToday}</p>
      </div>
      <div class="stat-card">
        <p class="stat-label">${t('onSiteNow')}</p>
        <p class="stat-value stat-green">${currentlyOnSite}</p>
      </div>
      <div class="stat-card">
        <p class="stat-label">${t('thisWeek')}</p>
        <p class="stat-value stat-orange">${weekVisitors}</p>
      </div>
    </div>

    <div class="admin-table-card">
      <div class="table-header">
        <h2 class="table-title">${t('recentVisits')}</h2>
      </div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>${t('name')}</th>
              <th>${t('company')}</th>
              <th>${t('host')}</th>
              <th>${t('arrivalTime')}</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            ${recentVisitors.length === 0 ? `
              <tr><td colspan="5" class="table-empty">${t('noVisitsToday')}</td></tr>
            ` : recentVisitors.map(v => `
              <tr>
                <td>${v.first_name} ${v.last_name}</td>
                <td>${v.company || ''}</td>
                <td>${v.host_name || v.host_email}</td>
                <td>${new Date(v.arrival_time).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}</td>
                <td>
                  ${v.status === 'checked_in'
                    ? `<span class="badge badge-green">${t('onSite')}</span>`
                    : `<span class="badge badge-gray">${t('departed')}</span>`
                  }
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

async function renderNowView() {
  const { data: visitors } = await supabase
    .from('visitors')
    .select('*')
    .eq('status', 'checked_in')
    .order('arrival_time', { ascending: false });

  const now = new Date();

  return `
    <div class="view-header">
      <div>
        <h2 class="view-title">Visitors On Site Now</h2>
        <p class="view-subtitle">Real-time view • Auto-refresh every 30s</p>
      </div>
      <button id="exportCsvBtn" class="btn-export">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
        </svg>
        Export CSV
      </button>
    </div>

    <div class="admin-table-card">
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Company</th>
              <th>Host</th>
              <th>Arrival</th>
              <th>Time On Site</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="nowViewTable">
            ${visitors && visitors.length > 0 ? visitors.map(v => {
              const arrivalTime = new Date(v.arrival_time);
              const minutesOnSite = Math.floor((now - arrivalTime) / (1000 * 60));
              const hoursOnSite = Math.floor(minutesOnSite / 60);
              const minsRemainder = minutesOnSite % 60;
              const timeOnSite = hoursOnSite > 0 ? `${hoursOnSite}h ${minsRemainder}m` : `${minutesOnSite}m`;
              const isOverdue = minutesOnSite > (v.expected_duration || 180);
              const statusClass = isOverdue ? 'badge-red' : minutesOnSite > (v.expected_duration || 180) * 0.8 ? 'badge-orange' : 'badge-green';

              return `
                <tr>
                  <td><strong>${v.first_name} ${v.last_name}</strong></td>
                  <td>${v.company || '-'}</td>
                  <td>${v.host_name || v.host_email}</td>
                  <td>${arrivalTime.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}</td>
                  <td>${timeOnSite}</td>
                  <td><span class="badge ${statusClass}">${isOverdue ? 'Overdue' : 'On Site'}</span></td>
                  <td>
                    <button class="btn-action-small" onclick="manualCheckout('${v.id}', '${v.first_name} ${v.last_name}')">
                      <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"></path>
                      </svg>
                      Check Out
                    </button>
                  </td>
                </tr>
              `;
            }).join('') : '<tr><td colspan="7" class="table-empty">No visitors on site</td></tr>'}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

async function renderAllVisitorsView() {
  const todayStart = new Date();
  todayStart.setHours(0, 0, 0, 0);

  const { data: visitors } = await supabase
    .from('visitors')
    .select('*')
    .gte('arrival_time', todayStart.toISOString())
    .order('arrival_time', { ascending: false })
    .limit(100);

  return `
    <div class="view-header">
      <div>
        <h2 class="view-title">All Visitors (Today)</h2>
        <p class="view-subtitle">Complete visitor log</p>
      </div>
      <button id="exportAllCsvBtn" class="btn-export">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
        </svg>
        Export CSV
      </button>
    </div>

    <div class="admin-table-card">
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Company</th>
              <th>Host</th>
              <th>Arrival</th>
              <th>Departure</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            ${visitors && visitors.length > 0 ? visitors.map(v => `
              <tr>
                <td><strong>${v.first_name} ${v.last_name}</strong></td>
                <td>${v.company || '-'}</td>
                <td>${v.host_name || v.host_email}</td>
                <td>${new Date(v.arrival_time).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}</td>
                <td>${v.departure_time ? new Date(v.departure_time).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '-'}</td>
                <td>
                  ${v.status === 'checked_in'
                    ? `<span class="badge badge-green">On Site</span>`
                    : `<span class="badge badge-gray">Departed</span>`
                  }
                </td>
              </tr>
            `).join('') : '<tr><td colspan="6" class="table-empty">No visitors today</td></tr>'}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

function renderQRPostersView() {
  const baseUrl = window.location.origin + window.location.pathname;
  const checkoutUrl = `${baseUrl}#/checkout`;

  return `
    <div class="view-header">
      <h2 class="view-title">QR Code Posters</h2>
      <p class="view-subtitle">Print these posters for meeting rooms</p>
    </div>

    <div class="qr-poster-container">
      <div class="qr-poster-card">
        <h3>Check-out QR Code</h3>
        <p>Place this poster in meeting rooms for visitors to scan when leaving</p>
        <div class="qr-poster-preview">
          <div id="posterQRCode" class="poster-qr"></div>
          <p class="poster-url">${checkoutUrl}</p>
        </div>
        <button onclick="window.print()" class="btn-primary">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
          </svg>
          Print Poster
        </button>
      </div>
    </div>
  `;
}

function startAutoRefresh() {
  if (refreshInterval) clearInterval(refreshInterval);
  refreshInterval = setInterval(async () => {
    if (currentView === 'now') {
      const tbody = document.getElementById('nowViewTable');
      if (tbody) {
        const content = await renderNowView();
        const parser = new DOMParser();
        const doc = parser.parseFromString(content, 'text/html');
        const newTbody = doc.getElementById('nowViewTable');
        if (newTbody) {
          tbody.innerHTML = newTbody.innerHTML;
        }
      }
    }
  }, 30000);
}

window.manualCheckout = function(visitorId, visitorName) {
  const modal = document.getElementById('manualCheckoutModal');
  const overlay = document.getElementById('modalOverlay');
  const nameEl = document.getElementById('modalVisitorName');

  nameEl.textContent = `Check out: ${visitorName}`;
  modal.classList.remove('hidden');
  overlay.classList.remove('hidden');

  document.getElementById('cancelCheckout').onclick = () => {
    modal.classList.add('hidden');
    overlay.classList.add('hidden');
  };

  document.getElementById('confirmCheckout').onclick = async () => {
    const reason = document.getElementById('checkoutReason').value;
    const notes = document.getElementById('checkoutNotes').value;

    await supabase.from('visitors').update({
      status: 'checked_out',
      departure_time: new Date().toISOString(),
      checkout_method: 'manual_admin',
      checkout_notes: `${reason}: ${notes}`,
    }).eq('id', visitorId);

    await supabase.from('audit_log').insert({
      action: 'manual_checkout',
      user_email: sessionStorage.getItem('admin_email'),
      visitor_id: visitorId,
      details: `Manual checkout: ${reason} - ${notes}`,
    });

    modal.classList.add('hidden');
    overlay.classList.add('hidden');
    renderView(currentView);
  };
};

// CSV Export
document.addEventListener('click', async (e) => {
  if (e.target.id === 'exportCsvBtn' || e.target.closest('#exportCsvBtn')) {
    await exportToCSV('now');
  }
  if (e.target.id === 'exportAllCsvBtn' || e.target.closest('#exportAllCsvBtn')) {
    await exportToCSV('all');
  }
});

async function exportToCSV(type) {
  let query = supabase.from('visitors').select('*').order('arrival_time', { ascending: false });

  if (type === 'now') {
    query = query.eq('status', 'checked_in');
  } else {
    const todayStart = new Date();
    todayStart.setHours(0, 0, 0, 0);
    query = query.gte('arrival_time', todayStart.toISOString());
  }

  const { data: visitors } = await query;

  if (!visitors || visitors.length === 0) {
    alert('No data to export');
    return;
  }

  const csv = [
    ['Name', 'First Name', 'Last Name', 'Company', 'Host', 'Host Email', 'Arrival Time', 'Departure Time', 'Duration (min)', 'Status'].join(','),
    ...visitors.map(v => {
      const arrival = new Date(v.arrival_time);
      const departure = v.departure_time ? new Date(v.departure_time) : null;
      const duration = departure ? Math.floor((departure - arrival) / (1000 * 60)) : '-';

      return [
        `"${v.first_name} ${v.last_name}"`,
        `"${v.first_name}"`,
        `"${v.last_name}"`,
        `"${v.company || ''}"`,
        `"${v.host_name || ''}"`,
        `"${v.host_email}"`,
        `"${arrival.toLocaleString('fr-FR')}"`,
        departure ? `"${departure.toLocaleString('fr-FR')}"` : '"-"',
        duration,
        `"${v.status}"`
      ].join(',');
    })
  ].join('\n');

  const blob = new Blob([csv], { type: 'text/csv' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `visitors-export-${new Date().toISOString().split('T')[0]}.csv`;
  a.click();
  URL.revokeObjectURL(url);
}

function getWeekStart() {
  const now = new Date();
  const day = now.getDay();
  const diff = now.getDate() - day + (day === 0 ? -6 : 1);
  const monday = new Date(now);
  monday.setDate(diff);
  monday.setHours(0, 0, 0, 0);
  return monday;
}
