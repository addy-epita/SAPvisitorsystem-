import "jsr:@supabase/functions-js/edge-runtime.d.ts";
import { createClient } from "npm:@supabase/supabase-js@2";

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Methods": "GET, POST, OPTIONS",
  "Access-Control-Allow-Headers": "Content-Type, Authorization, X-Client-Info, Apikey",
};

Deno.serve(async (req: Request) => {
  if (req.method === "OPTIONS") {
    return new Response(null, { status: 200, headers: corsHeaders });
  }

  try {
    const supabaseUrl = Deno.env.get('SUPABASE_URL')!;
    const supabaseKey = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!;
    const supabase = createClient(supabaseUrl, supabaseKey);

    const now = new Date();
    const currentHour = now.getHours();

    const { data: settings } = await supabase
      .from('settings')
      .select('setting_key, setting_value')
      .in('setting_key', ['eod_warning_hour', 'escalation_hour', 'safety_emails', 'enable_email_notifications']);

    const settingsMap = new Map(settings?.map((s: any) => [s.setting_key, s.setting_value]));
    const emailsEnabled = settingsMap.get('enable_email_notifications') !== 'false';

    if (!emailsEnabled) {
      return new Response(
        JSON.stringify({ message: 'Notifications désactivées' }),
        { headers: { ...corsHeaders, 'Content-Type': 'application/json' } }
      );
    }

    const eodWarningHour = parseInt(settingsMap.get('eod_warning_hour') || '21', 10);
    const escalationHour = parseInt(settingsMap.get('escalation_hour') || '22', 10);

    const { data: activeVisitors } = await supabase
      .from('visitors')
      .select('id, first_name, last_name, company, phone, host_email, host_name, arrival_time, expected_duration, expiry_notified, expiry_email_sent_at, reminder_sent, eod_notified, host_confirmed')
      .eq('status', 'checked_in')
      .is('host_confirmed', null);

    const visitors = activeVisitors || [];
    const stats = { expiry_checks: 0, reminders: 0, eod_warnings: 0, escalations: 0 };

    for (const visitor of visitors) {
      const arrivalTime = new Date(visitor.arrival_time);
      const expectedMs = (visitor.expected_duration || 120) * 60 * 1000;
      const expiryTime = new Date(arrivalTime.getTime() + expectedMs);
      const visitorName = `${visitor.first_name} ${visitor.last_name}`;

      if (!visitor.expiry_notified && now >= expiryTime) {
        const actionToken = crypto.randomUUID().replace(/-/g, '');
        const expiresAt = new Date(now.getTime() + 24 * 60 * 60 * 1000);

        await supabase.from('action_tokens').insert({
          visitor_id: visitor.id,
          token: actionToken,
          action_type: 'confirm_present',
          expires_at: expiresAt.toISOString(),
        });

        await fetch(`${supabaseUrl}/functions/v1/send-notification`, {
          method: 'POST',
          headers: { 'Authorization': `Bearer ${supabaseKey}`, 'Content-Type': 'application/json' },
          body: JSON.stringify({
            visitor_id: visitor.id,
            type: 'expiry_check',
            visitor_name: visitorName,
            company: visitor.company,
            phone: visitor.phone,
            host_email: visitor.host_email,
            host_name: visitor.host_name || visitor.host_email,
            arrival_time: visitor.arrival_time,
            expected_duration: visitor.expected_duration,
            action_token: actionToken,
          }),
        });

        await supabase.from('visitors').update({
          expiry_notified: true,
          expiry_email_sent_at: now.toISOString(),
          updated_at: now.toISOString(),
        }).eq('id', visitor.id);

        await supabase.from('audit_log').insert({
          action: 'expiry_check_sent',
          visitor_id: visitor.id,
          details: `Email de vérification d'expiration envoyé à ${visitor.host_email}`,
          created_at: now.toISOString(),
        });

        stats.expiry_checks++;
        continue;
      }

      if (
        visitor.expiry_notified &&
        !visitor.reminder_sent &&
        visitor.expiry_email_sent_at
      ) {
        const sentAt = new Date(visitor.expiry_email_sent_at);
        const oneHourLater = new Date(sentAt.getTime() + 60 * 60 * 1000);

        if (now >= oneHourLater) {
          const actionToken = crypto.randomUUID().replace(/-/g, '');
          const expiresAt = new Date(now.getTime() + 24 * 60 * 60 * 1000);

          await supabase.from('action_tokens').insert({
            visitor_id: visitor.id,
            token: actionToken,
            action_type: 'confirm_present',
            expires_at: expiresAt.toISOString(),
          });

          await fetch(`${supabaseUrl}/functions/v1/send-notification`, {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${supabaseKey}`, 'Content-Type': 'application/json' },
            body: JSON.stringify({
              visitor_id: visitor.id,
              type: 'expiry_reminder',
              visitor_name: visitorName,
              company: visitor.company,
              phone: visitor.phone,
              host_email: visitor.host_email,
              host_name: visitor.host_name || visitor.host_email,
              arrival_time: visitor.arrival_time,
              action_token: actionToken,
            }),
          });

          await supabase.from('visitors').update({
            reminder_sent: true,
            reminder_sent_at: now.toISOString(),
            updated_at: now.toISOString(),
          }).eq('id', visitor.id);

          await supabase.from('audit_log').insert({
            action: 'expiry_reminder_sent',
            visitor_id: visitor.id,
            details: `Email de rappel envoyé à ${visitor.host_email}`,
            created_at: now.toISOString(),
          });

          stats.reminders++;
          continue;
        }
      }

      if (currentHour === eodWarningHour && !visitor.eod_notified && visitor.expiry_notified) {
        const actionToken = crypto.randomUUID().replace(/-/g, '');
        const expiresAt = new Date(now.getTime() + 4 * 60 * 60 * 1000);

        await supabase.from('action_tokens').insert({
          visitor_id: visitor.id,
          token: actionToken,
          action_type: 'confirm_present',
          expires_at: expiresAt.toISOString(),
        });

        await fetch(`${supabaseUrl}/functions/v1/send-notification`, {
          method: 'POST',
          headers: { 'Authorization': `Bearer ${supabaseKey}`, 'Content-Type': 'application/json' },
          body: JSON.stringify({
            visitor_id: visitor.id,
            type: 'eod_warning',
            visitor_name: visitorName,
            company: visitor.company,
            phone: visitor.phone,
            host_email: visitor.host_email,
            host_name: visitor.host_name || visitor.host_email,
            arrival_time: visitor.arrival_time,
            action_token: actionToken,
          }),
        });

        await supabase.from('visitors').update({
          eod_notified: true,
          updated_at: now.toISOString(),
        }).eq('id', visitor.id);

        await supabase.from('audit_log').insert({
          action: 'eod_warning_sent',
          visitor_id: visitor.id,
          details: `Email d'avertissement fin de journée envoyé à ${visitor.host_email}`,
          created_at: now.toISOString(),
        });

        stats.eod_warnings++;
      }
    }

    if (currentHour === escalationHour) {
      const { data: overdueVisitors } = await supabase
        .from('visitors')
        .select('id, first_name, last_name, company, phone, host_email, host_name, arrival_time, expiry_notified')
        .eq('status', 'checked_in')
        .is('host_confirmed', null)
        .eq('expiry_notified', true);

      if (overdueVisitors && overdueVisitors.length > 0) {
        const safetyEmailsRaw = settingsMap.get('safety_emails');
        const safetyEmails: string[] = safetyEmailsRaw ? JSON.parse(safetyEmailsRaw) : [];

        if (safetyEmails.length > 0) {
          const overdueList = overdueVisitors.map((v: any) => ({
            name: `${v.first_name} ${v.last_name}`,
            company: v.company,
            phone: v.phone,
            host_name: v.host_name || v.host_email,
            arrival_time: v.arrival_time,
            hours_on_site: Math.floor((now.getTime() - new Date(v.arrival_time).getTime()) / (1000 * 60 * 60)),
          }));

          for (const safetyEmail of safetyEmails) {
            await fetch(`${supabaseUrl}/functions/v1/send-notification`, {
              method: 'POST',
              headers: { 'Authorization': `Bearer ${supabaseKey}`, 'Content-Type': 'application/json' },
              body: JSON.stringify({
                visitor_id: overdueVisitors[0].id,
                type: 'escalation',
                visitor_name: `${overdueVisitors.length} visiteur(s)`,
                company: 'Multiple',
                host_email: safetyEmail,
                host_name: 'Service Sécurité',
                arrival_time: now.toISOString(),
                overdue_visitors: overdueList,
              }),
            });
          }

          for (const v of overdueVisitors) {
            await supabase.from('audit_log').insert({
              action: 'escalation_sent',
              visitor_id: v.id,
              details: `Escalade envoyée au service sécurité (${safetyEmails.join(', ')})`,
              created_at: now.toISOString(),
            });
          }

          stats.escalations = overdueVisitors.length;
        }
      }
    }

    return new Response(
      JSON.stringify({ success: true, stats }),
      { headers: { ...corsHeaders, 'Content-Type': 'application/json' } }
    );
  } catch (error) {
    console.error('Erreur cron:', error);
    return new Response(
      JSON.stringify({ success: false, error: error.message }),
      { status: 500, headers: { ...corsHeaders, 'Content-Type': 'application/json' } }
    );
  }
});
