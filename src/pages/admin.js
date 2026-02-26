import { t } from '../i18n.js';
import { supabase } from '../supabase.js';

let adminSession = null;

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
            <a href="#/admin" class="sidebar-link active">${t('dashboard')}</a>
          </nav>
        </aside>

        <main class="admin-main">
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
        </main>
      </div>
    </div>
  `;

  document.getElementById('logoutBtn').addEventListener('click', () => {
    sessionStorage.clear();
    window.location.hash = '/';
  });
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
