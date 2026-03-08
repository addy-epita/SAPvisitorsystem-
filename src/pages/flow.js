export function renderFlow() {
  const app = document.getElementById('app');
  app.innerHTML = `
    <div class="flow-page animate-fade-in">

      <div class="flow-page-header">
        <a href="#/" class="flow-back-btn">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
          </svg>
          Retour
        </a>
        <div class="flow-page-title-block">
          <div class="flow-page-logo">SAP</div>
          <div>
            <h1 class="flow-page-title">Processus de Gestion des Visiteurs</h1>
            <p class="flow-page-subtitle">Service Aviation Paris — Vue d'ensemble du système</p>
          </div>
        </div>
        <button class="flow-print-btn" onclick="window.print()">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
          </svg>
          Imprimer
        </button>
      </div>

      <div class="flow-legend">
        <div class="flow-legend-item">
          <div class="flow-legend-dot fld-visitor"></div>
          <span>Action visiteur</span>
        </div>
        <div class="flow-legend-item">
          <div class="flow-legend-dot fld-system"></div>
          <span>Action système</span>
        </div>
        <div class="flow-legend-item">
          <div class="flow-legend-dot fld-host"></div>
          <span>Action hôte</span>
        </div>
        <div class="flow-legend-item">
          <div class="flow-legend-dot fld-alert"></div>
          <span>Alerte / Escalade</span>
        </div>
      </div>

      <div class="flow-body">

        <!-- PHASE 1 -->
        <div class="flow-phase-label">
          <span class="flow-phase-badge">PHASE 1</span>
          <span class="flow-phase-name">Arrivée &amp; Enregistrement</span>
        </div>

        <div class="flow-node fn-visitor">
          <div class="fn-num">1</div>
          <div class="fn-icon fn-icon-visitor">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <div class="fn-body">
            <div class="fn-title">Visiteur arrive à l'accueil</div>
            <div class="fn-desc">Le visiteur se dirige vers le terminal kiosque SAP dans le hall d'accueil</div>
          </div>
          <div class="fn-actor-tag fn-tag-visitor">Visiteur</div>
        </div>

        <div class="flow-connector"></div>

        <div class="flow-node fn-system">
          <div class="fn-num">2</div>
          <div class="fn-icon fn-icon-system">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
          </div>
          <div class="fn-body">
            <div class="fn-title">Enregistrement sur le kiosque</div>
            <div class="fn-desc">Saisie des informations : Nom · Société · Téléphone · Motif · Hôte · Durée prévue (2h, 4h ou 8h)</div>
          </div>
          <div class="fn-actor-tag fn-tag-system">Kiosque</div>
        </div>

        <div class="flow-connector"></div>

        <div class="flow-node fn-system">
          <div class="fn-num">3</div>
          <div class="fn-icon fn-icon-system">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="fn-body">
            <div class="fn-title">Confirmation &amp; QR code unique</div>
            <div class="fn-desc">Le visiteur reçoit un badge QR personnel, conservé pour son départ</div>
          </div>
          <div class="fn-actor-tag fn-tag-system">Système</div>
        </div>

        <div class="flow-connector"></div>

        <div class="flow-node fn-system">
          <div class="fn-num">4</div>
          <div class="fn-icon fn-icon-system">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
          </div>
          <div class="fn-body">
            <div class="fn-title">Notification automatique à l'hôte</div>
            <div class="fn-desc">Email envoyé immédiatement avec le profil du visiteur et des liens d'action rapide</div>
          </div>
          <div class="fn-actor-tag fn-tag-system">Système</div>
        </div>

        <!-- PHASE 2 -->
        <div class="flow-connector flow-connector-long"></div>

        <div class="flow-phase-label">
          <span class="flow-phase-badge">PHASE 2</span>
          <span class="flow-phase-name">Suivi &amp; Confirmation de Présence</span>
        </div>

        <div class="flow-node fn-host">
          <div class="fn-num">5</div>
          <div class="fn-icon fn-icon-host">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </div>
          <div class="fn-body">
            <div class="fn-title">L'hôte prend en charge le visiteur</div>
            <div class="fn-desc">L'hôte rejoint le visiteur et gère le déroulement de la visite</div>
          </div>
          <div class="fn-actor-tag fn-tag-host">Hôte</div>
        </div>

        <div class="flow-connector"></div>

        <div class="flow-split-container">
          <div class="flow-split-header">
            <div class="flow-decision-diamond">
              <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Durée prévue dépassée ?
            </div>
          </div>

          <div class="flow-split-tracks">
            <div class="flow-track flow-track-normal">
              <div class="flow-track-label flow-track-label-green">NON — Départ normal</div>
              <div class="flow-track-node fn-visitor fn-compact">
                <div class="fn-icon fn-icon-visitor">
                  <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 16l4-4m0 0l-4-4m4 4H7"/>
                  </svg>
                </div>
                <div class="fn-body">
                  <div class="fn-title">Le visiteur part de lui-même</div>
                  <div class="fn-desc">Le visiteur s'enregistre au kiosque avant la fin de la durée prévue</div>
                </div>
              </div>
              <div class="flow-track-arrow">↓</div>
              <div class="flow-track-node fn-system fn-compact">
                <div class="fn-icon fn-icon-system">
                  <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <div class="fn-body">
                  <div class="fn-title">Départ auto-enregistré</div>
                  <div class="fn-desc">Statut mis à jour automatiquement</div>
                </div>
              </div>
            </div>

            <div class="flow-split-divider"></div>

            <div class="flow-track flow-track-overtime">
              <div class="flow-track-label flow-track-label-orange">OUI — Surveillance active</div>
              <div class="flow-track-node fn-warning fn-compact">
                <div class="fn-icon fn-icon-warning">
                  <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                  </svg>
                </div>
                <div class="fn-body">
                  <div class="fn-title">Rappels automatiques</div>
                  <div class="fn-desc">Emails envoyés à l'hôte toutes les 2 heures avec liens d'action</div>
                </div>
              </div>
              <div class="flow-track-arrow">↓</div>
              <div class="flow-track-node fn-host fn-compact">
                <div class="fn-icon fn-icon-host">
                  <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
                <div class="fn-body">
                  <div class="fn-title">L'hôte confirme le statut</div>
                  <div class="fn-desc">Via email : « Toujours présent » ou « Il est parti »</div>
                </div>
              </div>
              <div class="flow-track-arrow">↓</div>
              <div class="flow-track-node fn-alert fn-compact">
                <div class="fn-icon fn-icon-alert">
                  <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                  </svg>
                </div>
                <div class="fn-body">
                  <div class="fn-title">21h00 — Escalade chefs de car</div>
                  <div class="fn-desc">Sans réponse : notification automatique au service de sécurité</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PHASE 3 -->
        <div class="flow-connector flow-connector-long"></div>

        <div class="flow-phase-label">
          <span class="flow-phase-badge">PHASE 3</span>
          <span class="flow-phase-name">Départ &amp; Clôture</span>
        </div>

        <div class="flow-node fn-visitor">
          <div class="fn-num">9</div>
          <div class="fn-icon fn-icon-visitor">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
          </div>
          <div class="fn-body">
            <div class="fn-title">Départ enregistré</div>
            <div class="fn-desc">Par le visiteur au kiosque (QR code ou nom), ou confirmé par l'hôte via email</div>
          </div>
          <div class="fn-actor-tag fn-tag-visitor">Visiteur / Hôte</div>
        </div>

        <div class="flow-connector"></div>

        <div class="flow-node fn-system">
          <div class="fn-num">10</div>
          <div class="fn-icon fn-icon-system">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="fn-body">
            <div class="fn-title">Email de confirmation de départ</div>
            <div class="fn-desc">Récapitulatif de visite envoyé à l'hôte — dossier clos dans le système</div>
          </div>
          <div class="fn-actor-tag fn-tag-system">Système</div>
        </div>

        <div class="flow-footer-note">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Toutes les données sont stockées de manière sécurisée et accessibles depuis le tableau de bord administrateur en temps réel.
        </div>

      </div>
    </div>
  `;
}
