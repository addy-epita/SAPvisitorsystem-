import "jsr:@supabase/functions-js/edge-runtime.d.ts";
import { createClient } from "npm:@supabase/supabase-js@2";
import nodemailer from "npm:nodemailer@6";

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Methods": "GET, POST, PUT, DELETE, OPTIONS",
  "Access-Control-Allow-Headers": "Content-Type, Authorization, X-Client-Info, Apikey",
};

interface NotificationRequest {
  visitor_id: number;
  type: 'arrival' | 'expiry_check' | 'expiry_reminder' | 'eod_warning' | 'escalation' | 'checkout';
  visitor_name: string;
  company: string;
  phone?: string;
  host_email: string;
  host_name: string;
  arrival_time: string;
  departure_time?: string;
  expected_duration?: number;
  action_token?: string;
  confirmed_by?: string;
  overdue_visitors?: Array<{ name: string; company: string; phone?: string; host_name: string; arrival_time: string; hours_on_site: number }>;
}

Deno.serve(async (req: Request) => {
  if (req.method === "OPTIONS") {
    return new Response(null, { status: 200, headers: corsHeaders });
  }

  try {
    const payload: NotificationRequest = await req.json();

    const gmailUser         = Deno.env.get('GMAIL_FROM_EMAIL')     || 'securigeek@gmail.com';
    const gmailPassword     = Deno.env.get('GMAIL_APP_PASSWORD')   || 'vekjykztmzogguqa';
    const fromName          = Deno.env.get('GMAIL_FROM_NAME')      || 'SAP Visitor System';
    const baseUrl           = Deno.env.get('BASE_URL')             || 'https://sapformations.com';
    const testEmailOverride = Deno.env.get('TEST_EMAIL_OVERRIDE')  || 'ambedkar.sharma@gmail.com';

    const resolveEmail = (email: string) => testEmailOverride || email;

    if (!gmailUser || !gmailPassword) {
      throw new Error('Identifiants Gmail non configurés (GMAIL_FROM_EMAIL / GMAIL_APP_PASSWORD)');
    }

    const transporter = nodemailer.createTransport({
      host: 'smtp.gmail.com',
      port: 587,
      secure: false,
      auth: { user: gmailUser, pass: gmailPassword },
    });

    const emailContent = buildEmailContent(payload, baseUrl);

    await transporter.sendMail({
      from: `"${fromName}" <${gmailUser}>`,
      to: resolveEmail(payload.host_email),
      subject: emailContent.subject,
      html: emailContent.body,
    });

    if (payload.type === 'arrival' || payload.type === 'checkout') {
      const supabase = createClient(
        Deno.env.get('SUPABASE_URL')!,
        Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!
      );
      const { data: safetySetting } = await supabase
        .from('settings')
        .select('setting_value')
        .eq('setting_key', 'safety_emails')
        .maybeSingle();

      if (safetySetting?.setting_value) {
        const safetyEmails: string[] = JSON.parse(safetySetting.setting_value);
        for (const email of safetyEmails) {
          await transporter.sendMail({
            from: `"${fromName}" <${gmailUser}>`,
            to: resolveEmail(email),
            subject: emailContent.subject,
            html: emailContent.body,
          });
        }
      }
    }

    return new Response(
      JSON.stringify({ success: true }),
      { headers: { ...corsHeaders, 'Content-Type': 'application/json' } }
    );
  } catch (error) {
    console.error('Erreur envoi notification:', error);
    return new Response(
      JSON.stringify({ success: false, error: error.message }),
      { status: 500, headers: { ...corsHeaders, 'Content-Type': 'application/json' } }
    );
  }
});

function formatDateTime(iso: string): string {
  return new Date(iso).toLocaleString('fr-FR', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}

function formatDuration(minutes: number): string {
  if (minutes >= 60) {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return m > 0 ? `${h}h${m.toString().padStart(2, '0')}` : `${h} heure${h > 1 ? 's' : ''}`;
  }
  return `${minutes} min`;
}

function emailBase(headerColor: string, headerTitle: string, body: string): string {
  return `<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; background: #F5F5F5; color: #333; }
  .wrap { max-width: 600px; margin: 32px auto; background: white; border-radius: 4px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
  .header { background: ${headerColor}; color: white; padding: 28px 32px; }
  .header-logo { font-size: 22px; font-weight: 700; letter-spacing: 2px; margin-bottom: 6px; }
  .header-title { font-size: 20px; font-weight: 600; }
  .content { padding: 32px; }
  .info-card { background: #F5F5F5; border-left: 4px solid ${headerColor}; border-radius: 0 4px 4px 0; padding: 16px 20px; margin: 20px 0; }
  .info-row { display: flex; gap: 8px; margin-bottom: 8px; font-size: 15px; }
  .info-row:last-child { margin-bottom: 0; }
  .info-label { font-weight: 600; color: #555; min-width: 120px; }
  .info-value { color: #222; }
  .btn-row { text-align: center; margin: 28px 0; }
  .btn { display: inline-block; padding: 13px 28px; margin: 6px 8px; text-decoration: none; border-radius: 4px; font-weight: 700; font-size: 16px; }
  .btn-green { background: #107E3E; color: white; }
  .btn-blue { background: #1140A9; color: white; }
  .note { font-size: 13px; color: #888; text-align: center; margin-top: 16px; }
  .footer { background: #F5F5F5; text-align: center; padding: 20px 32px; font-size: 13px; color: #9197A4; border-top: 1px solid #e8e8e8; }
  p { margin-bottom: 12px; line-height: 1.6; }
  .warning-badge { display: inline-block; background: #FFECD1; color: #C25A00; font-weight: 700; font-size: 13px; padding: 4px 10px; border-radius: 4px; margin-bottom: 16px; }
  .danger-badge { display: inline-block; background: #FFE0E0; color: #B00; font-weight: 700; font-size: 13px; padding: 4px 10px; border-radius: 4px; margin-bottom: 16px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div class="header-logo">SAP</div>
    <div class="header-title">${headerTitle}</div>
  </div>
  <div class="content">
    ${body}
  </div>
  <div class="footer">Service Aviation Paris &mdash; Gestion des Visiteurs</div>
</div>
</body>
</html>`;
}

function buildEmailContent(payload: NotificationRequest, baseUrl: string): { subject: string; body: string } {
  const confirmPresentUrl  = `${baseUrl}/#/confirm-action?token=${payload.action_token}&action=present`;
  const confirmDepartedUrl = `${baseUrl}/#/confirm-action?token=${payload.action_token}&action=departed`;

  switch (payload.type) {
    case 'arrival': {
      const subject = `[ARRIVÉE] ${payload.visitor_name} — ${payload.company}`;
      const body = emailBase('#1140A9', 'Nouveau Visiteur Arrivé', `
        <p>Bonjour <strong>${payload.host_name}</strong>,</p>
        <p>Un visiteur vient de s'enregistrer à la réception et vous a désigné comme hôte.</p>
        <div class="info-card">
          <div class="info-row"><span class="info-label">Visiteur</span><span class="info-value">${payload.visitor_name}</span></div>
          <div class="info-row"><span class="info-label">Société</span><span class="info-value">${payload.company}</span></div>
          ${payload.phone ? `<div class="info-row"><span class="info-label">Téléphone</span><span class="info-value">${payload.phone}</span></div>` : ''}
          <div class="info-row"><span class="info-label">Arrivée</span><span class="info-value">${formatDateTime(payload.arrival_time)}</span></div>
          ${payload.expected_duration ? `<div class="info-row"><span class="info-label">Durée prévue</span><span class="info-value">${formatDuration(payload.expected_duration)}</span></div>` : ''}
        </div>
        <p>Vous recevrez une notification lorsque la durée prévue de la visite sera écoulée.</p>
      `);
      return { subject, body };
    }

    case 'expiry_check': {
      const subject = `[ACTION REQUISE] ${payload.visitor_name} est-il encore sur site ?`;
      const body = emailBase('#E9730C', 'Confirmation de Présence Requise', `
        <p>Bonjour <strong>${payload.host_name}</strong>,</p>
        <div class="warning-badge">Durée de visite écoulée</div>
        <p>La durée prévue de la visite de <strong>${payload.visitor_name}</strong> est arrivée à son terme. Merci de confirmer sa situation.</p>
        <div class="info-card">
          <div class="info-row"><span class="info-label">Visiteur</span><span class="info-value">${payload.visitor_name}</span></div>
          <div class="info-row"><span class="info-label">Société</span><span class="info-value">${payload.company}</span></div>
          ${payload.phone ? `<div class="info-row"><span class="info-label">Téléphone</span><span class="info-value">${payload.phone}</span></div>` : ''}
          <div class="info-row"><span class="info-label">Arrivée</span><span class="info-value">${formatDateTime(payload.arrival_time)}</span></div>
          ${payload.expected_duration ? `<div class="info-row"><span class="info-label">Durée prévue</span><span class="info-value">${formatDuration(payload.expected_duration)}</span></div>` : ''}
        </div>
        <div class="btn-row">
          <a href="${confirmPresentUrl}" class="btn btn-green">Toujours sur site</a>
          <a href="${confirmDepartedUrl}" class="btn btn-blue">Il est parti</a>
        </div>
        <p class="note">Ces liens sont valides 24 heures.</p>
      `);
      return { subject, body };
    }

    case 'expiry_reminder': {
      const subject = `[RAPPEL] ${payload.visitor_name} — Confirmation toujours attendue`;
      const body = emailBase('#E9730C', 'Rappel — Présence Non Confirmée', `
        <p>Bonjour <strong>${payload.host_name}</strong>,</p>
        <div class="warning-badge">Rappel — 1 heure sans réponse</div>
        <p>Nous n'avons pas reçu de confirmation concernant <strong>${payload.visitor_name}</strong>. Merci de répondre dès que possible.</p>
        <div class="info-card">
          <div class="info-row"><span class="info-label">Visiteur</span><span class="info-value">${payload.visitor_name}</span></div>
          <div class="info-row"><span class="info-label">Société</span><span class="info-value">${payload.company}</span></div>
          ${payload.phone ? `<div class="info-row"><span class="info-label">Téléphone</span><span class="info-value">${payload.phone}</span></div>` : ''}
          <div class="info-row"><span class="info-label">Arrivée</span><span class="info-value">${formatDateTime(payload.arrival_time)}</span></div>
        </div>
        <div class="btn-row">
          <a href="${confirmPresentUrl}" class="btn btn-green">Toujours sur site</a>
          <a href="${confirmDepartedUrl}" class="btn btn-blue">Il est parti</a>
        </div>
        <p class="note">Sans réponse, ce dossier sera signalé au service de sécurité en fin de journée.</p>
      `);
      return { subject, body };
    }

    case 'eod_warning': {
      const subject = `[URGENT] ${payload.visitor_name} — Confirmation requise avant 21h00`;
      const body = emailBase('#BB0000', 'Confirmation Urgente — Fin de Journée', `
        <p>Bonjour <strong>${payload.host_name}</strong>,</p>
        <div class="danger-badge">Urgence — Fin de journée approche</div>
        <p>Il est bientôt 21h00 et nous n'avons toujours pas de confirmation concernant <strong>${payload.visitor_name}</strong>.</p>
        <p><strong>Si vous ne répondez pas avant 21h00, ce cas sera automatiquement escaladé au service de sécurité.</strong></p>
        <div class="info-card">
          <div class="info-row"><span class="info-label">Visiteur</span><span class="info-value">${payload.visitor_name}</span></div>
          <div class="info-row"><span class="info-label">Société</span><span class="info-value">${payload.company}</span></div>
          ${payload.phone ? `<div class="info-row"><span class="info-label">Téléphone</span><span class="info-value">${payload.phone}</span></div>` : ''}
          <div class="info-row"><span class="info-label">Arrivée</span><span class="info-value">${formatDateTime(payload.arrival_time)}</span></div>
        </div>
        <div class="btn-row">
          <a href="${confirmPresentUrl}" class="btn btn-green">Toujours sur site</a>
          <a href="${confirmDepartedUrl}" class="btn btn-blue">Il est parti</a>
        </div>
      `);
      return { subject, body };
    }

    case 'escalation': {
      const subject = `[ESCALADE SÉCURITÉ] Visiteurs non confirmés en fin de journée`;
      const visitorRows = (payload.overdue_visitors || []).map(v => `
        <tr>
          <td style="padding:10px 12px; border-bottom:1px solid #eee;">${v.name}</td>
          <td style="padding:10px 12px; border-bottom:1px solid #eee;">${v.company}</td>
          <td style="padding:10px 12px; border-bottom:1px solid #eee;">${v.phone || '—'}</td>
          <td style="padding:10px 12px; border-bottom:1px solid #eee;">${v.host_name}</td>
          <td style="padding:10px 12px; border-bottom:1px solid #eee;">${formatDateTime(v.arrival_time)}</td>
          <td style="padding:10px 12px; border-bottom:1px solid #eee; font-weight:700; color:#BB0000;">${v.hours_on_site}h</td>
        </tr>
      `).join('');

      const body = emailBase('#BB0000', 'Escalade — Visiteurs Non Confirmés', `
        <p>Bonjour,</p>
        <div class="danger-badge">Escalade Sécurité — ${new Date().toLocaleDateString('fr-FR')}</div>
        <p>Les visiteurs suivants sont toujours enregistrés comme présents sans confirmation de leur hôte.</p>
        <table style="width:100%; border-collapse:collapse; margin:20px 0; font-size:14px;">
          <thead>
            <tr style="background:#F5F5F5;">
              <th style="padding:10px 12px; text-align:left; border-bottom:2px solid #ddd;">Visiteur</th>
              <th style="padding:10px 12px; text-align:left; border-bottom:2px solid #ddd;">Société</th>
              <th style="padding:10px 12px; text-align:left; border-bottom:2px solid #ddd;">Téléphone</th>
              <th style="padding:10px 12px; text-align:left; border-bottom:2px solid #ddd;">Hôte</th>
              <th style="padding:10px 12px; text-align:left; border-bottom:2px solid #ddd;">Arrivée</th>
              <th style="padding:10px 12px; text-align:left; border-bottom:2px solid #ddd;">Sur site</th>
            </tr>
          </thead>
          <tbody>${visitorRows}</tbody>
        </table>
        <p>Veuillez prendre les mesures nécessaires pour vérifier la présence de ces personnes sur le site.</p>
      `);
      return { subject, body };
    }

    case 'checkout': {
      const subject = `[DÉPART] ${payload.visitor_name} — ${payload.company}`;
      const confirmedByLabel = payload.confirmed_by === 'host' ? "Confirmé par l'hôte" : 'Auto-enregistrement';
      const body = emailBase('#1140A9', 'Visiteur Parti', `
        <p>Bonjour <strong>${payload.host_name}</strong>,</p>
        <p>Le départ de votre visiteur a été enregistré.</p>
        <div class="info-card">
          <div class="info-row"><span class="info-label">Visiteur</span><span class="info-value">${payload.visitor_name}</span></div>
          <div class="info-row"><span class="info-label">Société</span><span class="info-value">${payload.company}</span></div>
          ${payload.phone ? `<div class="info-row"><span class="info-label">Téléphone</span><span class="info-value">${payload.phone}</span></div>` : ''}
          <div class="info-row"><span class="info-label">Arrivée</span><span class="info-value">${formatDateTime(payload.arrival_time)}</span></div>
          ${payload.departure_time ? `<div class="info-row"><span class="info-label">Départ</span><span class="info-value">${formatDateTime(payload.departure_time)}</span></div>` : ''}
          <div class="info-row"><span class="info-label">Mode</span><span class="info-value">${confirmedByLabel}</span></div>
        </div>
        <p>Merci pour votre accueil.</p>
      `);
      return { subject, body };
    }

    default:
      throw new Error(`Type de notification inconnu: ${(payload as any).type}`);
  }
}
