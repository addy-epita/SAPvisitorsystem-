import "jsr:@supabase/functions-js/edge-runtime.d.ts";
import { createClient } from "npm:@supabase/supabase-js@2";

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Methods": "GET, POST, OPTIONS",
  "Access-Control-Allow-Headers": "Content-Type, Authorization, X-Client-Info, Apikey",
};

Deno.serve(async (req: Request) => {
  if (req.method === "OPTIONS") {
    return new Response(null, {
      status: 200,
      headers: corsHeaders,
    });
  }

  try {
    const supabaseUrl = Deno.env.get('SUPABASE_URL')!;
    const supabaseKey = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!;
    const supabase = createClient(supabaseUrl, supabaseKey);

    const now = new Date();
    const currentHour = now.getHours();
    const currentMinutes = now.getMinutes();

    // Get settings
    const { data: settings } = await supabase
      .from('settings')
      .select('setting_key, setting_value')
      .in('setting_key', ['reminder_intervals', 'end_of_day_time', 'enable_email_notifications']);

    const settingsMap = new Map(settings?.map(s => [s.setting_key, s.setting_value]));
    const emailsEnabled = settingsMap.get('enable_email_notifications') === 'true';
    const reminderIntervals = JSON.parse(settingsMap.get('reminder_intervals') || '[240]'); // Default 4 hours
    const endOfDayTime = settingsMap.get('end_of_day_time') || '18:00';

    if (!emailsEnabled) {
      return new Response(
        JSON.stringify({ message: 'Email notifications are disabled' }),
        { headers: { ...corsHeaders, 'Content-Type': 'application/json' } }
      );
    }

    // Check for visitors needing reminders
    const { data: visitors } = await supabase
      .from('visitors')
      .select('id, first_name, last_name, company, host_email, host_name, arrival_time, expected_duration')
      .eq('status', 'checked_in');

    let remindersSent = 0;
    let escalationsSent = 0;

    for (const visitor of visitors || []) {
      const arrivalTime = new Date(visitor.arrival_time);
      const minutesElapsed = Math.floor((now.getTime() - arrivalTime.getTime()) / (1000 * 60));

      // Check if visitor needs a reminder
      const needsReminder = reminderIntervals.some((interval: number) => {
        const diff = Math.abs(minutesElapsed - interval);
        return diff < 15; // Within 15 minutes of reminder time
      });

      if (needsReminder) {
        // Check if reminder already sent recently
        const { data: recentNotifs } = await supabase
          .from('notifications')
          .select('id')
          .eq('visitor_id', visitor.id)
          .eq('type', 'reminder')
          .gte('sent_at', new Date(now.getTime() - 60 * 60 * 1000).toISOString()) // Last hour
          .limit(1);

        if (!recentNotifs || recentNotifs.length === 0) {
          // Generate new action token
          const actionToken = crypto.randomUUID().replace(/-/g, '');
          const expiresAt = new Date();
          expiresAt.setHours(expiresAt.getHours() + 24);

          await supabase.from('action_tokens').insert({
            visitor_id: visitor.id,
            token: actionToken,
            action_type: 'confirm_present',
            expires_at: expiresAt.toISOString(),
          });

          // Send reminder notification
          await fetch(`${supabaseUrl}/functions/v1/send-notification`, {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${supabaseKey}`,
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              visitor_id: visitor.id,
              type: 'reminder',
              visitor_name: `${visitor.first_name} ${visitor.last_name}`,
              company: visitor.company,
              host_email: visitor.host_email,
              host_name: visitor.host_name || visitor.host_email,
              arrival_time: visitor.arrival_time,
              action_token: actionToken,
            }),
          });

          // Log notification
          await supabase.from('notifications').insert({
            visitor_id: visitor.id,
            type: 'reminder',
            recipient_email: visitor.host_email,
            subject: `[RAPPEL] ${visitor.first_name} ${visitor.last_name} est-il toujours sur site ?`,
            status: 'sent',
            sent_at: now.toISOString(),
          });

          remindersSent++;
        }
      }
    }

    // Check for end-of-day escalation
    const [eodHour, eodMinute] = endOfDayTime.split(':').map(Number);
    const isEndOfDay = currentHour === eodHour && currentMinutes >= eodMinute && currentMinutes < eodMinute + 30;

    if (isEndOfDay) {
      // Get all still-checked-in visitors who are past expected duration
      const { data: overdueVisitors } = await supabase
        .from('visitors')
        .select('id, first_name, last_name, company, host_email, host_name, arrival_time, expected_duration')
        .eq('status', 'checked_in');

      const unconfirmedVisitors = overdueVisitors?.filter(v => {
        const arrivalTime = new Date(v.arrival_time);
        const minutesElapsed = Math.floor((now.getTime() - arrivalTime.getTime()) / (1000 * 60));
        return minutesElapsed > (v.expected_duration || 180);
      });

      if (unconfirmedVisitors && unconfirmedVisitors.length > 0) {
        // Get supervisor emails from settings or use default
        const { data: supervisorSetting } = await supabase
          .from('settings')
          .select('setting_value')
          .eq('setting_key', 'supervisor_emails')
          .maybeSingle();

        const supervisorEmails = supervisorSetting?.setting_value
          ? JSON.parse(supervisorSetting.setting_value)
          : ['chef.car@sap.com'];

        // Send escalation summary to each supervisor
        for (const supervisorEmail of supervisorEmails) {
          const escalationBody = buildEscalationEmail(unconfirmedVisitors);

          await fetch(`${supabaseUrl}/functions/v1/send-notification`, {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${supabaseKey}`,
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              visitor_id: unconfirmedVisitors[0].id,
              type: 'escalation',
              visitor_name: `${unconfirmedVisitors.length} visitor(s)`,
              company: 'Multiple',
              host_email: supervisorEmail,
              host_name: 'Supervisor',
              arrival_time: now.toISOString(),
            }),
          });

          escalationsSent++;
        }

        // Log escalation notifications
        for (const visitor of unconfirmedVisitors) {
          await supabase.from('notifications').insert({
            visitor_id: visitor.id,
            type: 'escalation',
            recipient_email: supervisorEmails[0],
            subject: '[ESCALATION] Visiteurs non confirmés',
            status: 'sent',
            sent_at: now.toISOString(),
          });
        }
      }
    }

    return new Response(
      JSON.stringify({
        success: true,
        message: 'Reminder cron executed successfully',
        reminders_sent: remindersSent,
        escalations_sent: escalationsSent,
      }),
      {
        headers: {
          ...corsHeaders,
          'Content-Type': 'application/json',
        },
      }
    );
  } catch (error) {
    console.error('Cron error:', error);
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

function buildEscalationEmail(visitors: any[]) {
  const visitorList = visitors.map(v => {
    const arrivalTime = new Date(v.arrival_time);
    const hoursOnSite = Math.floor((Date.now() - arrivalTime.getTime()) / (1000 * 60 * 60));
    return `- ${v.first_name} ${v.last_name} (${v.company}) - Hôte: ${v.host_name || v.host_email} - ${hoursOnSite}h sur site`;
  }).join('\n');

  return `
    Bonjour,

    Les visiteurs suivants sont toujours enregistrés comme présents sans confirmation récente :

    ${visitorList}

    Veuillez vérifier leur présence effective sur site.

    Cordialement,
    SAP Visitor Management System
  `;
}
