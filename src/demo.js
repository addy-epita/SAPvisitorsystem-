const DEMO_KEY = 'sap_demo_mode';

export function isDemoMode() {
  return sessionStorage.getItem(DEMO_KEY) === 'true';
}

export function enableDemoMode() {
  sessionStorage.setItem(DEMO_KEY, 'true');
  updateDemoBanner();
}

export function disableDemoMode() {
  sessionStorage.removeItem(DEMO_KEY);
  updateDemoBanner();
}

export function toggleDemoMode() {
  if (isDemoMode()) {
    disableDemoMode();
  } else {
    enableDemoMode();
  }
}

export function updateDemoBanner() {
  const existing = document.getElementById('demo-banner');
  if (isDemoMode()) {
    if (!existing) {
      const banner = document.createElement('div');
      banner.id = 'demo-banner';
      banner.className = 'demo-banner';
      banner.innerHTML = `
        <span class="demo-banner-icon">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </span>
        <span>MODE DÉMO ACTIF — Aucune donnée réelle ne sera enregistrée</span>
        <button class="demo-banner-close" id="demo-banner-close">Désactiver</button>
      `;
      document.body.insertBefore(banner, document.body.firstChild);
      document.getElementById('demo-banner-close').addEventListener('click', () => {
        disableDemoMode();
      });
    }
  } else {
    if (existing) existing.remove();
  }
}

export const DEMO_VISITOR = {
  id: 999999,
  first_name: 'Jean',
  last_name: 'Dupont',
  company: 'Air France Industries',
  phone: '+33 6 12 34 56 78',
  reason: 'Réunion commerciale',
  host_email: 'contact@sapformations.com',
  host_name: 'Marie Martin',
  visitor_email: 'jean.dupont@airfrance.fr',
  arrival_time: new Date().toISOString(),
  expected_duration: 120,
  status: 'checked_in',
  qr_token: 'demo_qr_' + Math.random().toString(36).slice(2, 10),
};

export const DEMO_HOSTS = [
  { id: 1, name: 'Marie Martin', email: 'marie.martin@sapformations.com', department: 'Formation' },
  { id: 2, name: 'Pierre Dupuis', email: 'pierre.dupuis@sapformations.com', department: 'Direction' },
  { id: 3, name: 'Sophie Bernard', email: 'sophie.bernard@sapformations.com', department: 'Sécurité' },
];
