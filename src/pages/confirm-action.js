import { getRouteParams } from '../router.js';
import { supabase } from '../supabase.js';

export async function renderConfirmAction() {
  const params = getRouteParams();
  const token = params.token;
  const action = params.action;

  const app = document.getElementById('app');

  if (!token || !action) {
    renderError('Paramètres manquants. Ce lien semble invalide.');
    return;
  }

  app.innerHTML = `
    <div class="confirmation-layout">
      <div class="confirmation-container animate-fade-in" style="text-align:center;">
        <div class="spinner-large" style="margin:0 auto 20px; border-color:rgba(17,64,169,0.2); border-top-color:#1140A9;"></div>
        <p style="color:var(--text-secondary);">Traitement de votre confirmation...</p>
      </div>
    </div>
  `;

  try {
    const { data: actionToken, error: tokenError } = await supabase
      .from('action_tokens')
      .select('id, visitor_id, action_type, used_at, expires_at')
      .eq('token', token)
      .maybeSingle();

    if (tokenError || !actionToken) {
      renderError('Lien invalide ou expiré. Veuillez contacter la réception.');
      return;
    }

    if (actionToken.used_at) {
      renderError('Ce lien a déjà été utilisé.');
      return;
    }

    if (new Date(actionToken.expires_at) < new Date()) {
      renderError('Ce lien a expiré. Veuillez contacter la réception.');
      return;
    }

    const { data: visitor, error: visitorError } = await supabase
      .from('visitors')
      .select('id, first_name, last_name, company, phone, host_name, host_email, arrival_time, status')
      .eq('id', actionToken.visitor_id)
      .maybeSingle();

    if (visitorError || !visitor) {
      renderError('Visiteur introuvable.');
      return;
    }

    if (visitor.status === 'checked_out') {
      renderError('Ce visiteur a déjà été enregistré comme parti.');
      return;
    }

    if (action === 'departed') {
      const { error: updateError } = await supabase
        .from('visitors')
        .update({
          status: 'checked_out',
          departure_time: new Date().toISOString(),
          checkout_method: 'host_confirmed',
          host_confirmed: 'departed',
          updated_at: new Date().toISOString(),
        })
        .eq('id', visitor.id);

      if (updateError) {
        renderError('Impossible de mettre à jour le statut du visiteur.');
        return;
      }

      await supabase
        .from('action_tokens')
        .update({ used_at: new Date().toISOString() })
        .eq('id', actionToken.id);

      await supabase.from('audit_log').insert({
        action: 'host_confirmed_departure',
        visitor_id: visitor.id,
        details: `Hôte a confirmé le départ de ${visitor.first_name} ${visitor.last_name}`,
        created_at: new Date().toISOString(),
      });

      const supabaseUrl = import.meta.env.VITE_SUPABASE_URL;
      const supabaseKey = import.meta.env.VITE_SUPABASE_ANON_KEY;

      fetch(`${supabaseUrl}/functions/v1/send-notification`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${supabaseKey}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          visitor_id: visitor.id,
          type: 'checkout',
          visitor_name: `${visitor.first_name} ${visitor.last_name}`,
          company: visitor.company,
          phone: visitor.phone,
          host_email: visitor.host_email,
          host_name: visitor.host_name || visitor.host_email,
          arrival_time: visitor.arrival_time,
          departure_time: new Date().toISOString(),
          confirmed_by: 'host',
        }),
      }).catch(err => console.error('Notification error:', err));

      renderSuccess(
        'Départ Confirmé',
        `Merci d'avoir confirmé que ${visitor.first_name} ${visitor.last_name} a quitté les lieux.`,
        'Le départ a été enregistré avec succès dans le système.'
      );

    } else if (action === 'present') {
      await supabase
        .from('visitors')
        .update({
          host_confirmed: 'present',
          updated_at: new Date().toISOString(),
        })
        .eq('id', visitor.id);

      await supabase
        .from('action_tokens')
        .update({ used_at: new Date().toISOString() })
        .eq('id', actionToken.id);

      await supabase.from('audit_log').insert({
        action: 'host_confirmed_present',
        visitor_id: visitor.id,
        details: `Hôte a confirmé la présence de ${visitor.first_name} ${visitor.last_name}`,
        created_at: new Date().toISOString(),
      });

      renderSuccess(
        'Présence Confirmée',
        `Merci d'avoir confirmé que ${visitor.first_name} ${visitor.last_name} est toujours sur place.`,
        'Aucune autre action n\'est nécessaire pour le moment.'
      );

    } else {
      renderError('Type d\'action inconnu.');
    }

  } catch (error) {
    console.error('Error processing action:', error);
    renderError('Une erreur inattendue s\'est produite. Veuillez réessayer.');
  }
}

function renderSuccess(title, message, submessage) {
  const app = document.getElementById('app');
  app.innerHTML = `
    <div class="confirmation-layout">
      <div class="confirmation-container animate-fade-in">
        <div class="confirmation-icon-wrap confirmation-icon-green">
          <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        <h1 class="confirmation-title">${title}</h1>
        <div class="confirmation-message-box">
          <p class="confirmation-message">${message}</p>
          <p class="confirmation-sub">${submessage}</p>
        </div>
        <p style="color:var(--text-muted); font-size:0.9rem; margin-top:20px;">
          Vous pouvez fermer cette page en toute sécurité.
        </p>
      </div>
    </div>
  `;
}

function renderError(message) {
  const app = document.getElementById('app');
  app.innerHTML = `
    <div class="confirmation-layout">
      <div class="confirmation-container animate-fade-in">
        <div class="confirmation-icon-wrap confirmation-icon-red">
          <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </div>
        <h1 class="confirmation-title">Erreur</h1>
        <div class="confirmation-message-box">
          <p class="confirmation-message">${message}</p>
        </div>
        <p style="color:var(--text-muted); font-size:0.9rem; margin-top:20px;">
          Si vous pensez qu'il s'agit d'une erreur, veuillez contacter la réception.
        </p>
      </div>
    </div>
  `;
}
