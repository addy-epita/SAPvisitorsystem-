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
          <img src="/assets/images/logo_sap_def_fevrier.jpg" alt="Service Aviation Paris" class="flow-page-logo">
          <div>
            <h1 class="flow-page-title">Processus d'accueil des visiteurs</h1>
            <p class="flow-page-subtitle">Service Aviation Paris — Aéroport d'Orly</p>
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

      <!-- Security Banner -->
      <div class="flow-security-banner">
        <div class="flow-security-banner-icon">
          <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
        </div>
        <div class="flow-security-banner-body">
          <div class="flow-security-banner-title">Accès réservé — Aérogare PCZSAR, Orly</div>
          <div class="flow-security-banner-text">L'accès aux zones aéroportuaires est réservé aux personnes munies d'un badge préfectoral valide. Le document de sécurité SAP est envoyé automatiquement par email à chaque visiteur lors de son enregistrement.</div>
        </div>
        <div class="flow-security-banner-emergency">
          <div class="flow-security-banner-em-label">Urgence</div>
          <div class="flow-security-banner-em-number">01.49.75.48.15</div>
          <div class="flow-security-banner-em-number">06.60.62.69.77</div>
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
            <div class="fn-title">Le visiteur arrive à l'accueil SAP</div>
            <div class="fn-desc">Il se présente au terminal kiosque dans le hall d'accueil du Service Aviation Paris, Aéroport d'Orly</div>
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
            <div class="fn-desc">Saisie des informations : Nom · Prénom · Société · Téléphone · Email · Motif de la visite · Hôte SAP · Durée prévue (2h, 4h ou 8h)</div>
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
            <div class="fn-title">Badge QR code personnel délivré</div>
            <div class="fn-desc">Un badge QR unique est généré et remis au visiteur. Il doit être porté de manière visible durant toute la visite et restitué à la sortie.</div>
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
            <div class="fn-title">Email de bienvenue envoyé au visiteur</div>
            <div class="fn-desc">Le visiteur reçoit automatiquement un email avec son badge QR et la brochure de sécurité SAP (consignes d'urgence et règles de sécurité de l'Aéroport d'Orly) en pièce jointe.</div>
          </div>
          <div class="fn-actor-tag fn-tag-system">Système</div>
        </div>

        <div class="flow-connector"></div>

        <div class="flow-node fn-host">
          <div class="fn-num">5</div>
          <div class="fn-icon fn-icon-host">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
          </div>
          <div class="fn-body">
            <div class="fn-title">Notification immédiate à l'hôte SAP</div>
            <div class="fn-desc">L'employé SAP hôte (badge rouge) est averti par email avec le profil complet du visiteur et des liens d'action rapide pour gérer la visite</div>
          </div>
          <div class="fn-actor-tag fn-tag-host">Hôte</div>
        </div>

        <!-- Security Rules Card -->
        <div class="flow-connector"></div>
        <div class="flow-rules-card">
          <div class="flow-rules-title">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            Rappel des consignes de sécurité en vigueur
          </div>
          <div class="flow-rules-grid">
            <div class="flow-rule-item flow-rule-forbidden">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
              </svg>
              <span>Alcool et drogues illicites strictement interdits</span>
            </div>
            <div class="flow-rule-item flow-rule-forbidden">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
              </svg>
              <span>Téléphone portable interdit à l'extérieur des bureaux</span>
            </div>
            <div class="flow-rule-item flow-rule-forbidden">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
              </svg>
              <span>Fumer uniquement en zone fumeurs désignée</span>
            </div>
            <div class="flow-rule-item flow-rule-required">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span>Rester avec l'hôte SAP (badge rouge) en permanence</span>
            </div>
            <div class="flow-rule-item flow-rule-required">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span>EPI obligatoires pour l'accès en piste</span>
            </div>
            <div class="flow-rule-item flow-rule-required">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span>En cas d'alarme : se rendre au point de rassemblement le plus proche</span>
            </div>
          </div>
        </div>

        <!-- PHASE 2 -->
        <div class="flow-connector flow-connector-long"></div>

        <div class="flow-phase-label">
          <span class="flow-phase-badge">PHASE 2</span>
          <span class="flow-phase-name">Suivi &amp; Gestion de la Durée</span>
        </div>

        <div class="flow-node fn-host">
          <div class="fn-num">6</div>
          <div class="fn-icon fn-icon-host">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </div>
          <div class="fn-body">
            <div class="fn-title">L'hôte accueille et accompagne le visiteur</div>
            <div class="fn-desc">L'hôte SAP (badge rouge) rejoint le visiteur, l'accompagne en permanence dans les locaux et supervise le bon déroulement de la visite</div>
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
              <div class="flow-track-label flow-track-label-green">NON — Départ dans les temps</div>
              <div class="flow-track-node fn-visitor fn-compact">
                <div class="fn-icon fn-icon-visitor">
                  <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 16l4-4m0 0l-4-4m4 4H7"/>
                  </svg>
                </div>
                <div class="fn-body">
                  <div class="fn-title">Le visiteur se déclare au départ</div>
                  <div class="fn-desc">Il scanne son QR code ou saisit son nom au kiosque avant la fin de la durée prévue, et restitue son badge</div>
                </div>
              </div>
            </div>

            <div class="flow-split-divider"></div>

            <div class="flow-track flow-track-overtime">
              <div class="flow-track-label flow-track-label-orange">OUI — Surveillance renforcée</div>
              <div class="flow-track-node fn-warning fn-compact">
                <div class="fn-icon fn-icon-warning">
                  <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                  </svg>
                </div>
                <div class="fn-body">
                  <div class="fn-title">Relances automatiques toutes les 2 heures</div>
                  <div class="fn-desc">Des emails de rappel sont envoyés à l'hôte avec des liens d'action directe : confirmer la présence ou enregistrer le départ</div>
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
                  <div class="fn-title">L'hôte répond au rappel</div>
                  <div class="fn-desc">Via email : « Le visiteur est toujours présent » ou « Il est parti » — le système met à jour le statut en temps réel</div>
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
                  <div class="fn-title">21h00 — Escalade au chef de car</div>
                  <div class="fn-desc">Sans réponse de l'hôte avant 21h00 : notification automatique envoyée au responsable de sécurité SAP pour prise en charge immédiate</div>
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
            <div class="fn-title">Départ enregistré &amp; badge restitué</div>
            <div class="fn-desc">Le visiteur scanne son QR code au kiosque ou l'hôte confirme le départ via email. Le badge est rendu obligatoirement avant de quitter les locaux.</div>
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
            <div class="fn-title">Confirmation de départ envoyée à l'hôte</div>
            <div class="fn-desc">Un email récapitulatif de la visite est transmis à l'hôte SAP (durée, horaires). Le dossier est automatiquement clos dans le système.</div>
          </div>
          <div class="fn-actor-tag fn-tag-system">Système</div>
        </div>

        <div class="flow-footer-note">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Toutes les données sont stockées de manière sécurisée et consultables en temps réel depuis le tableau de bord administrateur SAP.
        </div>

      </div>
    </div>
  `;
}
