import "jsr:@supabase/functions-js/edge-runtime.d.ts";
import { createClient } from "npm:@supabase/supabase-js@2";
import nodemailer from "npm:nodemailer@6";

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Methods": "GET, POST, PUT, DELETE, OPTIONS",
  "Access-Control-Allow-Headers": "Content-Type, Authorization, X-Client-Info, Apikey",
};

interface CurrentVisitor {
  first_name: string;
  last_name: string;
  company: string;
  phone?: string;
  host_name: string;
  arrival_time: string;
  expected_duration?: number;
}

interface NotificationRequest {
  visitor_id: number;
  type: 'arrival' | 'expiry_check' | 'expiry_reminder' | 'eod_warning' | 'escalation' | 'checkout' | 'security_arrival';
  visitor_name: string;
  company: string;
  phone?: string;
  visitor_email?: string;
  host_email: string;
  host_name: string;
  arrival_time: string;
  departure_time?: string;
  expected_duration?: number;
  action_token?: string;
  confirmed_by?: string;
  overdue_visitors?: Array<{ name: string; company: string; phone?: string; host_name: string; arrival_time: string; hours_on_site: number }>;
  current_visitors?: CurrentVisitor[];
  total_on_site?: number;
}

Deno.serve(async (req: Request) => {
  if (req.method === "OPTIONS") {
    return new Response(null, { status: 200, headers: corsHeaders });
  }

  try {
    const payload: NotificationRequest = await req.json();

    const gmailUser     = Deno.env.get('GMAIL_FROM_EMAIL')   || 'securigeek@gmail.com';
    const gmailPassword = Deno.env.get('GMAIL_APP_PASSWORD') || 'vekjykztmzogguqa';
    const fromName      = Deno.env.get('GMAIL_FROM_NAME')    || 'SAP Visitor System';
    const baseUrl       = Deno.env.get('BASE_URL')           || 'https://sapformations.com';

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
      to: payload.host_email,
      subject: emailContent.subject,
      html: emailContent.body,
    });

    if (payload.type === 'arrival') {
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

        const { data: currentVisitors } = await supabase
          .from('visitors')
          .select('first_name, last_name, company, phone, host_name, arrival_time, expected_duration')
          .is('departure_time', null)
          .order('arrival_time', { ascending: true });

        const securityPayload: NotificationRequest = {
          ...payload,
          type: 'security_arrival',
          current_visitors: (currentVisitors || []) as CurrentVisitor[],
          total_on_site: (currentVisitors || []).length,
        };

        const securityEmail = buildEmailContent(securityPayload, baseUrl);

        for (const email of safetyEmails) {
          await transporter.sendMail({
            from: `"${fromName}" <${gmailUser}>`,
            to: email,
            subject: securityEmail.subject,
            html: securityEmail.body,
          });
        }
      }

      if (payload.visitor_email) {
        const welcomeEmail = buildVisitorWelcomeEmail(payload, baseUrl);
        const mailOptions: Record<string, unknown> = {
          from: `"${fromName}" <${gmailUser}>`,
          to: payload.visitor_email,
          subject: welcomeEmail.subject,
          html: welcomeEmail.body,
        };

        try {
          const pdfUrl = `${baseUrl}/uploads/ACCEUIL_VISITEUR_VF-_1.pdf`;
          const pdfResponse = await fetch(pdfUrl);
          if (pdfResponse.ok) {
            const pdfBuffer = await pdfResponse.arrayBuffer();
            mailOptions.attachments = [{
              filename: 'Consignes_Securite_SAP.pdf',
              content: Buffer.from(pdfBuffer),
              contentType: 'application/pdf',
            }];
          }
        } catch (_) {
        }

        await transporter.sendMail(mailOptions);
      }
    }

    if (payload.type === 'checkout') {
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
            to: email,
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

function formatTime(iso: string): string {
  return new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

function formatDuration(minutes: number): string {
  if (minutes >= 60) {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return m > 0 ? `${h}h${m.toString().padStart(2, '0')}` : `${h} heure${h > 1 ? 's' : ''}`;
  }
  return `${minutes} min`;
}

function personSvg(color = 'white', size = 18): string {
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="${size}" height="${size}" fill="${color}" style="vertical-align:middle;display:inline-block;"><path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm0 2c-3.33 0-10 1.67-10 5v2h20v-2c0-3.33-6.67-5-10-5z"/></svg>`;
}

function shieldSvg(color = '#B71C1C', size = 22): string {
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="${size}" height="${size}" fill="${color}" style="vertical-align:middle;display:inline-block;flex-shrink:0;"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>`;
}

function warningSvg(color = '#B71C1C', size = 16): string {
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="${size}" height="${size}" fill="${color}" style="vertical-align:middle;display:inline-block;flex-shrink:0;margin-right:6px;"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>`;
}

function checkinBadge(): string {
  return `<div style="display:inline-flex;align-items:center;gap:8px;background:#107E3E;color:white;padding:8px 18px;border-radius:24px;font-weight:700;font-size:14px;letter-spacing:.4px;margin-bottom:20px;">${personSvg()} &nbsp;ENTRÉE ENREGISTRÉE</div>`;
}

function checkoutBadge(): string {
  return `<div style="display:inline-flex;align-items:center;gap:8px;background:#546E7A;color:white;padding:8px 18px;border-radius:24px;font-weight:700;font-size:14px;letter-spacing:.4px;margin-bottom:20px;">${personSvg()} &nbsp;SORTIE ENREGISTRÉE</div>`;
}

function securityNoticeBlock(): string {
  return `
<div style="border:1px solid #FFCDD2;border-radius:6px;overflow:hidden;margin-top:28px;">
  <div style="background:#B71C1C;padding:12px 18px;display:flex;align-items:center;gap:10px;">
    ${shieldSvg('white', 20)}
    <span style="color:white;font-weight:700;font-size:14px;letter-spacing:.3px;">Notification de sécurité — Accueil de visiteur</span>
  </div>
  <div style="background:#FFF8F8;padding:16px 18px;">
    <p style="margin:0 0 10px;font-size:14px;color:#4A0000;line-height:1.6;">
      Nous vous rappelons qu'en tant que personne d'accueil, vous êtes responsable de la sécurité de votre visiteur durant toute la durée de sa présence dans nos locaux.
    </p>
    <p style="margin:0 0 10px;font-size:14px;color:#4A0000;font-weight:600;">En cas de déclenchement d'une alarme, vous êtes tenu(e) de :</p>
    <table style="width:100%;border-collapse:collapse;">
      <tr>
        <td style="padding:5px 0 5px 4px;vertical-align:top;width:24px;">${warningSvg('#B71C1C', 15)}</td>
        <td style="padding:5px 0;font-size:14px;color:#4A0000;line-height:1.5;">Accompagner immédiatement votre visiteur vers la sortie la plus proche</td>
      </tr>
      <tr>
        <td style="padding:5px 0 5px 4px;vertical-align:top;width:24px;">${warningSvg('#B71C1C', 15)}</td>
        <td style="padding:5px 0;font-size:14px;color:#4A0000;line-height:1.5;">Vous diriger ensemble vers le point de rassemblement désigné</td>
      </tr>
      <tr>
        <td style="padding:5px 0 5px 4px;vertical-align:top;width:24px;">${warningSvg('#B71C1C', 15)}</td>
        <td style="padding:5px 0;font-size:14px;color:#4A0000;line-height:1.5;">Suivre l'ensemble des consignes de sécurité en vigueur</td>
      </tr>
    </table>
    <p style="margin:10px 0 0;font-size:13px;color:#6A0000;font-style:italic;">Nous comptons sur votre vigilance et votre coopération.</p>
  </div>
</div>`;
}

function emailBase(headerColor: string, headerTitle: string, body: string, logoUrl: string): string {
  return `<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; background: #F0F0F0; color: #333; }
  .wrap { max-width: 600px; margin: 32px auto; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.10); }
  .header { background: ${headerColor}; color: white; padding: 24px 32px 22px; }
  .header-title { font-size: 19px; font-weight: 600; margin-top: 12px; }
  .content { padding: 32px; }
  .info-card { background: #F7F7F7; border-left: 4px solid ${headerColor}; border-radius: 0 4px 4px 0; padding: 16px 20px; margin: 20px 0; }
  .info-row { display: flex; gap: 8px; margin-bottom: 8px; font-size: 15px; }
  .info-row:last-child { margin-bottom: 0; }
  .info-label { font-weight: 600; color: #555; min-width: 130px; }
  .info-value { color: #222; }
  .btn-row { text-align: center; margin: 28px 0; }
  .btn { display: inline-block; padding: 13px 28px; margin: 6px 8px; text-decoration: none; border-radius: 4px; font-weight: 700; font-size: 16px; }
  .btn-green { background: #107E3E; color: white; }
  .btn-blue { background: #1140A9; color: white; }
  .note { font-size: 13px; color: #888; text-align: center; margin-top: 16px; }
  .footer { background: #F0F0F0; text-align: center; padding: 20px 32px; font-size: 13px; color: #9197A4; border-top: 1px solid #e0e0e0; }
  p { margin-bottom: 12px; line-height: 1.6; }
  .warning-badge { display: inline-block; background: #FFECD1; color: #C25A00; font-weight: 700; font-size: 13px; padding: 4px 10px; border-radius: 4px; margin-bottom: 16px; }
  .danger-badge { display: inline-block; background: #FFE0E0; color: #B00; font-weight: 700; font-size: 13px; padding: 4px 10px; border-radius: 4px; margin-bottom: 16px; }
  .visitor-count { background: #E8F5E9; border: 1px solid #A5D6A7; border-radius: 6px; padding: 14px 20px; margin: 20px 0; font-size: 16px; color: #1B5E20; font-weight: 600; text-align: center; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <img src="${logoUrl}/assets/images/logo.png" alt="SAP" style="height:44px;display:block;" />
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

function buildVisitorWelcomeEmail(payload: NotificationRequest, baseUrl: string): { subject: string; body: string } {
  const subject = `Bienvenue chez SRP — ${payload.visitor_name}`;

  const infoSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="#1140A9" style="vertical-align:middle;display:inline-block;flex-shrink:0;margin-right:6px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>`;

  const body = emailBase('#1140A9', 'Bienvenue à bord', `
    <p>Bonjour <strong>${payload.visitor_name}</strong>,</p>
    <p>Nous vous souhaitons la bienvenue dans les locaux de <strong>Service Aviation Paris (SRP)</strong>. Votre visite a bien été enregistrée.</p>
    <div class="info-card">
      <div class="info-row"><span class="info-label">Visiteur</span><span class="info-value">${payload.visitor_name}</span></div>
      <div class="info-row"><span class="info-label">Société</span><span class="info-value">${payload.company}</span></div>
      <div class="info-row"><span class="info-label">Votre hôte</span><span class="info-value">${payload.host_name}</span></div>
      <div class="info-row"><span class="info-label">Arrivée</span><span class="info-value">${formatDateTime(payload.arrival_time)}</span></div>
      ${payload.expected_duration ? `<div class="info-row"><span class="info-label">Durée prévue</span><span class="info-value">${formatDuration(payload.expected_duration)}</span></div>` : ''}
    </div>

    <div style="border:1px solid #FFCDD2;border-radius:6px;overflow:hidden;margin-top:24px;">
      <div style="background:#B71C1C;padding:12px 18px;display:flex;align-items:center;gap:10px;">
        ${shieldSvg('white', 20)}
        <span style="color:white;font-weight:700;font-size:14px;letter-spacing:.3px;">Consignes de sécurité</span>
      </div>
      <div style="background:#FFF8F8;padding:16px 18px;">
        <p style="margin:0 0 10px;font-size:14px;color:#4A0000;line-height:1.6;">
          Pour votre sécurité et celle de tous, merci de prendre connaissance des consignes en vigueur dans nos locaux.
        </p>
        <table style="width:100%;border-collapse:collapse;">
          <tr>
            <td style="padding:5px 0 5px 4px;vertical-align:top;width:24px;">${infoSvg}</td>
            <td style="padding:5px 0;font-size:14px;color:#333;line-height:1.5;">Restez accompagné(e) de votre hôte en tout temps dans les zones sécurisées</td>
          </tr>
          <tr>
            <td style="padding:5px 0 5px 4px;vertical-align:top;width:24px;">${infoSvg}</td>
            <td style="padding:5px 0;font-size:14px;color:#333;line-height:1.5;">En cas d'alarme, suivez les instructions de votre hôte et rejoignez le point de rassemblement</td>
          </tr>
          <tr>
            <td style="padding:5px 0 5px 4px;vertical-align:top;width:24px;">${infoSvg}</td>
            <td style="padding:5px 0;font-size:14px;color:#333;line-height:1.5;">Ne laissez pas de bagages ou objets sans surveillance</td>
          </tr>
          <tr>
            <td style="padding:5px 0 5px 4px;vertical-align:top;width:24px;">${infoSvg}</td>
            <td style="padding:5px 0;font-size:14px;color:#333;line-height:1.5;">Présentez votre badge visiteur à tout agent de sécurité qui vous le demande</td>
          </tr>
        </table>
        <p style="margin:12px 0 0;font-size:13px;color:#6A0000;font-style:italic;">
          Un document de sécurité détaillé vous sera transmis sous peu. Merci de votre coopération.
        </p>
      </div>
    </div>
    <p style="margin-top:24px;font-size:14px;color:#555;">Nous vous souhaitons une excellente visite. N'hésitez pas à contacter la réception si vous avez besoin d'aide.</p>
  `, baseUrl);

  return { subject, body };
}

function buildEmailContent(payload: NotificationRequest, baseUrl: string): { subject: string; body: string } {
  const confirmPresentUrl  = `${baseUrl}/#/confirm-action?token=${payload.action_token}&action=present`;
  const confirmDepartedUrl = `${baseUrl}/#/confirm-action?token=${payload.action_token}&action=departed`;

  switch (payload.type) {
    case 'arrival': {
      const subject = `[ARRIVÉE] ${payload.visitor_name} — ${payload.company}`;
      const body = emailBase('#1140A9', 'Nouveau Visiteur Arrivé', `
        <p>Bonjour <strong>${payload.host_name}</strong>,</p>
        ${checkinBadge()}
        <p>Un visiteur vient de s'enregistrer à la réception et vous a désigné comme hôte.</p>
        <div class="info-card">
          <div class="info-row"><span class="info-label">Visiteur</span><span class="info-value">${payload.visitor_name}</span></div>
          <div class="info-row"><span class="info-label">Société</span><span class="info-value">${payload.company}</span></div>
          ${payload.phone ? `<div class="info-row"><span class="info-label">Téléphone</span><span class="info-value">${payload.phone}</span></div>` : ''}
          <div class="info-row"><span class="info-label">Arrivée</span><span class="info-value">${formatDateTime(payload.arrival_time)}</span></div>
          ${payload.expected_duration ? `<div class="info-row"><span class="info-label">Durée prévue</span><span class="info-value">${formatDuration(payload.expected_duration)}</span></div>` : ''}
        </div>
        <p>Vous recevrez une notification lorsque la durée prévue de la visite sera écoulée.</p>
        ${securityNoticeBlock()}
      `, baseUrl);
      return { subject, body };
    }

    case 'security_arrival': {
      const visitors = payload.current_visitors || [];
      const total = payload.total_on_site || 0;
      const subject = `[SÉCURITÉ] Nouvel arrivant — ${payload.visitor_name} · ${total} visiteur${total > 1 ? 's' : ''} sur site`;

      const visitorRows = visitors.map((v, i) => {
        const fullName = `${v.first_name} ${v.last_name}`;
        const isNew = fullName.trim() === payload.visitor_name.trim();
        const rowBg = isNew ? '#E8F5E9' : (i % 2 === 0 ? '#FAFAFA' : 'white');
        return `
        <tr style="background:${rowBg};">
          <td style="padding:10px 12px;border-bottom:1px solid #eee;">
            ${isNew ? `<strong>${fullName}</strong> <span style="background:#107E3E;color:white;font-size:11px;padding:2px 7px;border-radius:3px;font-weight:700;margin-left:4px;">NOUVEAU</span>` : fullName}
          </td>
          <td style="padding:10px 12px;border-bottom:1px solid #eee;">${v.company}</td>
          <td style="padding:10px 12px;border-bottom:1px solid #eee;">${v.phone || '—'}</td>
          <td style="padding:10px 12px;border-bottom:1px solid #eee;">${v.host_name}</td>
          <td style="padding:10px 12px;border-bottom:1px solid #eee;">${formatTime(v.arrival_time)}</td>
          <td style="padding:10px 12px;border-bottom:1px solid #eee;">${v.expected_duration ? formatDuration(v.expected_duration) : '—'}</td>
        </tr>`;
      }).join('');

      const body = emailBase('#107E3E', 'Notification Sécurité — Arrivée Visiteur', `
        <p>Bonjour,</p>
        ${checkinBadge()}
        <p>Un nouveau visiteur vient de s'enregistrer à la réception.</p>
        <div class="info-card">
          <div class="info-row"><span class="info-label">Visiteur</span><span class="info-value"><strong>${payload.visitor_name}</strong></span></div>
          <div class="info-row"><span class="info-label">Société</span><span class="info-value">${payload.company}</span></div>
          ${payload.phone ? `<div class="info-row"><span class="info-label">Téléphone</span><span class="info-value">${payload.phone}</span></div>` : ''}
          <div class="info-row"><span class="info-label">Hôte</span><span class="info-value">${payload.host_name}</span></div>
          <div class="info-row"><span class="info-label">Arrivée</span><span class="info-value">${formatDateTime(payload.arrival_time)}</span></div>
          ${payload.expected_duration ? `<div class="info-row"><span class="info-label">Durée prévue</span><span class="info-value">${formatDuration(payload.expected_duration)}</span></div>` : ''}
        </div>
        <div class="visitor-count">
          ${personSvg('#1B5E20', 20)} &nbsp; <strong>${total}</strong> visiteur${total > 1 ? 's' : ''} actuellement présent${total > 1 ? 's' : ''} sur le site
        </div>
        ${visitors.length > 0 ? `
        <p style="margin-bottom:10px;"><strong>Liste complète des visiteurs présents :</strong></p>
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
          <thead>
            <tr style="background:#107E3E;color:white;">
              <th style="padding:10px 12px;text-align:left;">Visiteur</th>
              <th style="padding:10px 12px;text-align:left;">Société</th>
              <th style="padding:10px 12px;text-align:left;">Téléphone</th>
              <th style="padding:10px 12px;text-align:left;">Hôte</th>
              <th style="padding:10px 12px;text-align:left;">Arrivée</th>
              <th style="padding:10px 12px;text-align:left;">Durée</th>
            </tr>
          </thead>
          <tbody>${visitorRows}</tbody>
        </table>` : ''}
      `, baseUrl);
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
      `, baseUrl);
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
      `, baseUrl);
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
      `, baseUrl);
      return { subject, body };
    }

    case 'escalation': {
      const subject = `[ESCALADE SÉCURITÉ] Visiteurs non confirmés en fin de journée`;
      const visitorRows = (payload.overdue_visitors || []).map(v => `
        <tr>
          <td style="padding:10px 12px;border-bottom:1px solid #eee;">${v.name}</td>
          <td style="padding:10px 12px;border-bottom:1px solid #eee;">${v.company}</td>
          <td style="padding:10px 12px;border-bottom:1px solid #eee;">${v.phone || '—'}</td>
          <td style="padding:10px 12px;border-bottom:1px solid #eee;">${v.host_name}</td>
          <td style="padding:10px 12px;border-bottom:1px solid #eee;">${formatDateTime(v.arrival_time)}</td>
          <td style="padding:10px 12px;border-bottom:1px solid #eee;font-weight:700;color:#BB0000;">${v.hours_on_site}h</td>
        </tr>
      `).join('');

      const body = emailBase('#BB0000', 'Escalade — Visiteurs Non Confirmés', `
        <p>Bonjour,</p>
        <div class="danger-badge">Escalade Sécurité — ${new Date().toLocaleDateString('fr-FR')}</div>
        <p>Les visiteurs suivants sont toujours enregistrés comme présents sans confirmation de leur hôte.</p>
        <table style="width:100%;border-collapse:collapse;margin:20px 0;font-size:14px;">
          <thead>
            <tr style="background:#F5F5F5;">
              <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #ddd;">Visiteur</th>
              <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #ddd;">Société</th>
              <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #ddd;">Téléphone</th>
              <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #ddd;">Hôte</th>
              <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #ddd;">Arrivée</th>
              <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #ddd;">Sur site</th>
            </tr>
          </thead>
          <tbody>${visitorRows}</tbody>
        </table>
        <p>Veuillez prendre les mesures nécessaires pour vérifier la présence de ces personnes sur le site.</p>
      `, baseUrl);
      return { subject, body };
    }

    case 'checkout': {
      const subject = `[DÉPART] ${payload.visitor_name} — ${payload.company}`;
      const confirmedByLabel = payload.confirmed_by === 'host' ? "Confirmé par l'hôte" : 'Auto-enregistrement';
      const body = emailBase('#546E7A', 'Visiteur Parti', `
        <p>Bonjour <strong>${payload.host_name}</strong>,</p>
        ${checkoutBadge()}
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
      `, baseUrl);
      return { subject, body };
    }

    default:
      throw new Error(`Type de notification inconnu: ${(payload as any).type}`);
  }
}
