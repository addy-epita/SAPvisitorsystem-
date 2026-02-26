import { t } from '../i18n.js';
import { getRouteParams } from '../router.js';
import { supabase } from '../supabase.js';

export async function renderConfirmAction() {
  const params = getRouteParams();
  const token = params.token;
  const action = params.action;

  const app = document.getElementById('app');

  if (!token || !action) {
    renderError('Missing required parameters');
    return;
  }

  app.innerHTML = `
    <div class="confirmation-layout">
      <div class="confirmation-container animate-fade-in">
        <div class="spinner-large"></div>
        <p style="color: white; margin-top: 20px;">Processing your request...</p>
      </div>
    </div>
  `;

  try {
    // Validate token and check if it's not expired or used
    const { data: actionToken, error: tokenError } = await supabase
      .from('action_tokens')
      .select('id, visitor_id, action_type, used_at, expires_at')
      .eq('token', token)
      .maybeSingle();

    if (tokenError || !actionToken) {
      renderError('Invalid or expired token');
      return;
    }

    if (actionToken.used_at) {
      renderError('This link has already been used');
      return;
    }

    if (new Date(actionToken.expires_at) < new Date()) {
      renderError('This link has expired');
      return;
    }

    // Get visitor details
    const { data: visitor, error: visitorError } = await supabase
      .from('visitors')
      .select('id, first_name, last_name, company, host_name, status')
      .eq('id', actionToken.visitor_id)
      .maybeSingle();

    if (visitorError || !visitor) {
      renderError('Visitor not found');
      return;
    }

    // Perform the action
    if (action === 'departed') {
      // Mark visitor as checked out
      const { error: updateError } = await supabase
        .from('visitors')
        .update({
          status: 'checked_out',
          departure_time: new Date().toISOString(),
          checkout_method: 'host_confirmed',
          updated_at: new Date().toISOString(),
        })
        .eq('id', visitor.id);

      if (updateError) {
        renderError('Failed to update visitor status');
        return;
      }

      // Mark token as used
      await supabase
        .from('action_tokens')
        .update({ used_at: new Date().toISOString() })
        .eq('id', actionToken.id);

      // Log the action
      await supabase.from('audit_log').insert({
        action: 'host_confirmed_departure',
        visitor_id: visitor.id,
        details: `Host confirmed departure via email link for ${visitor.first_name} ${visitor.last_name}`,
        created_at: new Date().toISOString(),
      });

      renderSuccess(
        'Departure Confirmed',
        `Thank you for confirming that ${visitor.first_name} ${visitor.last_name} has left.`,
        'The visitor has been checked out successfully.'
      );
    } else if (action === 'present') {
      // Just mark token as used and log confirmation
      await supabase
        .from('action_tokens')
        .update({ used_at: new Date().toISOString() })
        .eq('id', actionToken.id);

      await supabase.from('audit_log').insert({
        action: 'host_confirmed_present',
        visitor_id: visitor.id,
        details: `Host confirmed ${visitor.first_name} ${visitor.last_name} is still on site`,
        created_at: new Date().toISOString(),
      });

      renderSuccess(
        'Presence Confirmed',
        `Thank you for confirming that ${visitor.first_name} ${visitor.last_name} is still on site.`,
        'No further action needed at this time.'
      );
    } else {
      renderError('Invalid action type');
    }
  } catch (error) {
    console.error('Error processing action:', error);
    renderError('An unexpected error occurred');
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
        <p style="color: rgba(255,255,255,0.5); font-size: 0.9rem; margin-top: 20px;">
          You can safely close this page.
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
        <div class="confirmation-icon-wrap" style="background: rgba(187,0,0,0.2); color: #ef4444;">
          <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </div>
        <h1 class="confirmation-title">Error</h1>
        <div class="confirmation-message-box">
          <p class="confirmation-message">${message}</p>
        </div>
        <p style="color: rgba(255,255,255,0.5); font-size: 0.9rem; margin-top: 20px;">
          If you believe this is an error, please contact reception.
        </p>
      </div>
    </div>
  `;
}
