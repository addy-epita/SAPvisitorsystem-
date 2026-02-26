import "jsr:@supabase/functions-js/edge-runtime.d.ts";

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Methods": "GET, POST, PUT, DELETE, OPTIONS",
  "Access-Control-Allow-Headers": "Content-Type, Authorization, X-Client-Info, Apikey",
};

interface NotificationRequest {
  visitor_id: number;
  type: 'arrival' | 'reminder' | 'escalation' | 'checkout';
  visitor_name: string;
  company: string;
  host_email: string;
  host_name: string;
  arrival_time: string;
  action_token?: string;
}

Deno.serve(async (req: Request) => {
  if (req.method === "OPTIONS") {
    return new Response(null, {
      status: 200,
      headers: corsHeaders,
    });
  }

  try {
    const payload: NotificationRequest = await req.json();

    // Get Microsoft Graph API credentials from environment
    const clientId = Deno.env.get('MICROSOFT_CLIENT_ID');
    const tenantId = Deno.env.get('MICROSOFT_TENANT_ID');
    const clientSecret = Deno.env.get('MICROSOFT_CLIENT_SECRET');
    const fromEmail = Deno.env.get('FROM_EMAIL') || 'noreply@sap.com';

    if (!clientId || !tenantId || !clientSecret) {
      throw new Error('Microsoft Graph API credentials not configured');
    }

    // Get access token from Microsoft Graph
    const tokenResponse = await fetch(
      `https://login.microsoftonline.com/${tenantId}/oauth2/v2.0/token`,
      {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          client_id: clientId,
          client_secret: clientSecret,
          scope: 'https://graph.microsoft.com/.default',
          grant_type: 'client_credentials',
        }),
      }
    );

    if (!tokenResponse.ok) {
      throw new Error('Failed to obtain access token');
    }

    const { access_token } = await tokenResponse.json();

    // Build email content based on type
    const baseUrl = Deno.env.get('BASE_URL') || 'http://localhost:5173';
    const emailContent = buildEmailContent(payload, baseUrl);

    // Send email via Microsoft Graph API
    const sendResponse = await fetch(
      `https://graph.microsoft.com/v1.0/users/${fromEmail}/sendMail`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${access_token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          message: {
            subject: emailContent.subject,
            body: {
              contentType: 'HTML',
              content: emailContent.body,
            },
            toRecipients: [
              {
                emailAddress: {
                  address: payload.host_email,
                },
              },
            ],
          },
          saveToSentItems: true,
        }),
      }
    );

    if (!sendResponse.ok) {
      const errorText = await sendResponse.text();
      throw new Error(`Failed to send email: ${errorText}`);
    }

    return new Response(
      JSON.stringify({
        success: true,
        message: 'Notification sent successfully',
      }),
      {
        headers: {
          ...corsHeaders,
          'Content-Type': 'application/json',
        },
      }
    );
  } catch (error) {
    console.error('Error sending notification:', error);
    return new Response(
      JSON.stringify({
        success: false,
        error: error.message,
      }),
      {
        status: 500,
        headers: {
          ...corsHeaders,
          'Content-Type': 'application/json',
        },
      }
    );
  }
});

function buildEmailContent(payload: NotificationRequest, baseUrl: string) {
  const arrivalTime = new Date(payload.arrival_time).toLocaleString('fr-FR', {
    dateStyle: 'short',
    timeStyle: 'short',
  });

  const confirmPresentUrl = `${baseUrl}/#/confirm-action?token=${payload.action_token}&action=present`;
  const confirmDepartedUrl = `${baseUrl}/#/confirm-action?token=${payload.action_token}&action=departed`;

  switch (payload.type) {
    case 'arrival':
      return {
        subject: `[VISITEUR ARRIVÉ] ${payload.visitor_name} pour ${payload.host_name}`,
        body: `
          <!DOCTYPE html>
          <html>
          <head>
            <style>
              body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
              .container { max-width: 600px; margin: 0 auto; padding: 20px; }
              .header { background: #0070A8; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
              .content { background: #f5f5f5; padding: 30px; }
              .info-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
              .button { display: inline-block; padding: 12px 30px; margin: 10px 5px; text-decoration: none; border-radius: 6px; font-weight: bold; }
              .button-green { background: #107E3E; color: white; }
              .button-blue { background: #0070A8; color: white; }
              .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
          </head>
          <body>
            <div class="container">
              <div class="header">
                <h1>Visiteur Arrivé</h1>
              </div>
              <div class="content">
                <p>Bonjour ${payload.host_name},</p>
                <p>Votre visiteur est arrivé :</p>
                <div class="info-box">
                  <p><strong>Nom :</strong> ${payload.visitor_name}</p>
                  <p><strong>Société :</strong> ${payload.company}</p>
                  <p><strong>Heure d'arrivée :</strong> ${arrivalTime}</p>
                </div>
                <p>Pour confirmer la présence ou le départ de votre visiteur, cliquez sur l'un des boutons ci-dessous :</p>
                <div style="text-align: center; margin: 30px 0;">
                  <a href="${confirmPresentUrl}" class="button button-green">Toujours sur site</a>
                  <a href="${confirmDepartedUrl}" class="button button-blue">Parti</a>
                </div>
                <p><em>Ces liens sont valides pendant 24 heures.</em></p>
              </div>
              <div class="footer">
                <p>SAP Visitor Management System</p>
              </div>
            </div>
          </body>
          </html>
        `,
      };

    case 'reminder':
      return {
        subject: `[RAPPEL] ${payload.visitor_name} est-il toujours sur site ?`,
        body: `
          <!DOCTYPE html>
          <html>
          <head>
            <style>
              body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
              .container { max-width: 600px; margin: 0 auto; padding: 20px; }
              .header { background: #E9730C; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
              .content { background: #f5f5f5; padding: 30px; }
              .info-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
              .button { display: inline-block; padding: 12px 30px; margin: 10px 5px; text-decoration: none; border-radius: 6px; font-weight: bold; }
              .button-green { background: #107E3E; color: white; }
              .button-blue { background: #0070A8; color: white; }
              .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
          </head>
          <body>
            <div class="container">
              <div class="header">
                <h1>Rappel de Visite</h1>
              </div>
              <div class="content">
                <p>Bonjour ${payload.host_name},</p>
                <p>Votre visiteur <strong>${payload.visitor_name}</strong> de <strong>${payload.company}</strong> est-il toujours sur site ?</p>
                <div class="info-box">
                  <p><strong>Arrivé le :</strong> ${arrivalTime}</p>
                </div>
                <p>Veuillez confirmer son statut en cliquant sur l'un des boutons ci-dessous :</p>
                <div style="text-align: center; margin: 30px 0;">
                  <a href="${confirmPresentUrl}" class="button button-green">Toujours sur site</a>
                  <a href="${confirmDepartedUrl}" class="button button-blue">Parti</a>
                </div>
              </div>
              <div class="footer">
                <p>SAP Visitor Management System</p>
              </div>
            </div>
          </body>
          </html>
        `,
      };

    case 'checkout':
      return {
        subject: `[CONFIRMATION] ${payload.visitor_name} a quitté le site`,
        body: `
          <!DOCTYPE html>
          <html>
          <head>
            <style>
              body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
              .container { max-width: 600px; margin: 0 auto; padding: 20px; }
              .header { background: #0070A8; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
              .content { background: #f5f5f5; padding: 30px; }
              .info-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
              .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
          </head>
          <body>
            <div class="container">
              <div class="header">
                <h1>Visiteur Parti</h1>
              </div>
              <div class="content">
                <p>Bonjour ${payload.host_name},</p>
                <p>Votre visiteur a quitté le site :</p>
                <div class="info-box">
                  <p><strong>Nom :</strong> ${payload.visitor_name}</p>
                  <p><strong>Société :</strong> ${payload.company}</p>
                  <p><strong>Heure de départ :</strong> ${new Date().toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' })}</p>
                </div>
              </div>
              <div class="footer">
                <p>SAP Visitor Management System</p>
              </div>
            </div>
          </body>
          </html>
        `,
      };

    default:
      throw new Error(`Unknown notification type: ${payload.type}`);
  }
}
