# Microsoft Graph API Setup Guide

This guide explains how to configure Microsoft Graph API for sending emails from the SAP Visitor Management System.

---

## Prerequisites

- Microsoft 365 account with admin access
- Azure Active Directory access
- The email address you want to send from (e.g., `noreply@sap.com`)

---

## Step 1: Register Application in Azure

1. **Go to Azure Portal**
   - Visit: https://portal.azure.com
   - Sign in with your Microsoft 365 admin account

2. **Navigate to Azure Active Directory**
   - Click "Azure Active Directory" in the left menu
   - Or search for "Azure Active Directory" in the top search bar

3. **Register New Application**
   - Click "App registrations" in the left sidebar
   - Click "New registration"
   - Fill in:
     - **Name**: `SAP Visitor Management`
     - **Supported account types**: `Accounts in this organizational directory only`
     - **Redirect URI**: Leave blank (not needed for backend)
   - Click "Register"

4. **Note Your IDs**
   - After registration, you'll see the app overview page
   - Copy and save these values:
     - **Application (client) ID** → This is your `MICROSOFT_CLIENT_ID`
     - **Directory (tenant) ID** → This is your `MICROSOFT_TENANT_ID`

---

## Step 2: Create Client Secret

1. **Go to Certificates & Secrets**
   - In your app registration, click "Certificates & secrets" in left menu

2. **Create New Secret**
   - Click "New client secret"
   - Description: `Visitor Management Secret`
   - Expires: Choose duration (recommended: 24 months)
   - Click "Add"

3. **Copy Secret Value**
   - **IMPORTANT**: Copy the secret **Value** immediately!
   - You won't be able to see it again after leaving this page
   - This is your `MICROSOFT_CLIENT_SECRET`
   - Store it securely

---

## Step 3: Configure API Permissions

1. **Go to API Permissions**
   - Click "API permissions" in left menu

2. **Add Microsoft Graph Permissions**
   - Click "Add a permission"
   - Select "Microsoft Graph"
   - Select "Application permissions" (NOT Delegated)

3. **Add Required Permissions**
   - Search and select:
     - ✅ `Mail.Send` - Send mail as any user
   - Click "Add permissions"

4. **Grant Admin Consent**
   - Click "Grant admin consent for [Your Organization]"
   - Click "Yes" to confirm
   - Status should show green checkmarks

---

## Step 4: Configure Mailbox Access

The application needs permission to send emails from your designated mailbox.

### Option A: Using Service Account (Recommended)

1. **Create a dedicated mailbox** (if not exists)
   - Email: `noreply@sap.com` or `visitor-system@sap.com`

2. **No additional mailbox configuration needed**
   - Application permissions allow sending as any user
   - Works immediately after admin consent

### Option B: Using Shared Mailbox

1. **Create shared mailbox in Microsoft 365 admin center**
2. **No user license required for shared mailboxes**
3. **Same application permissions apply**

---

## Step 5: Configure Environment Variables

Add these secrets to your Supabase Edge Functions:

```bash
# In your terminal or Supabase dashboard
MICROSOFT_CLIENT_ID=12345678-1234-1234-1234-123456789abc
MICROSOFT_TENANT_ID=87654321-4321-4321-4321-cba987654321
MICROSOFT_CLIENT_SECRET=abc123~DefGhi456~JklMno789
FROM_EMAIL=noreply@sap.com
BASE_URL=https://yourdomain.com
```

### How to Add to Supabase

**Via Supabase Dashboard:**
1. Go to your Supabase project
2. Click "Edge Functions" in left menu
3. Click "Manage secrets"
4. Add each variable and value
5. Click "Save"

**Via Supabase CLI:**
```bash
supabase secrets set MICROSOFT_CLIENT_ID=your_value
supabase secrets set MICROSOFT_TENANT_ID=your_value
supabase secrets set MICROSOFT_CLIENT_SECRET=your_value
supabase secrets set FROM_EMAIL=noreply@sap.com
supabase secrets set BASE_URL=https://yourdomain.com
```

---

## Step 6: Test Email Sending

### Test via Supabase Dashboard

1. Go to Edge Functions → `send-notification`
2. Click "Invoke function"
3. Use this test payload:

```json
{
  "visitor_id": 1,
  "type": "arrival",
  "visitor_name": "Jean Dupont",
  "company": "Test Company",
  "host_email": "your-test-email@sap.com",
  "host_name": "Test Host",
  "arrival_time": "2024-02-24T14:30:00Z",
  "action_token": "test123token456"
}
```

4. Check the response:
   - Success: `{"success": true, "message": "Notification sent successfully"}`
   - Error: Check error message for debugging

5. Verify email received in inbox

### Test via API Call

```bash
curl -X POST https://your-project.supabase.co/functions/v1/send-notification \
  -H "Authorization: Bearer YOUR_SUPABASE_ANON_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "visitor_id": 1,
    "type": "arrival",
    "visitor_name": "Jean Dupont",
    "company": "Test Company",
    "host_email": "test@sap.com",
    "host_name": "Test Host",
    "arrival_time": "2024-02-24T14:30:00Z",
    "action_token": "abc123"
  }'
```

---

## Troubleshooting

### Error: "Failed to obtain access token"

**Cause**: Invalid credentials or expired secret

**Solution**:
- Verify CLIENT_ID, TENANT_ID, and CLIENT_SECRET are correct
- Check if client secret has expired
- Generate new secret if needed

### Error: "Failed to send email"

**Cause**: Missing permissions or invalid mailbox

**Solution**:
- Verify `Mail.Send` permission is granted
- Verify admin consent was completed (green checkmarks)
- Check FROM_EMAIL matches an actual mailbox
- Wait 10 minutes after granting consent (propagation delay)

### Error: "Recipient not found"

**Cause**: Invalid host email address

**Solution**:
- Verify host email exists in your organization
- Check for typos in email address
- Ensure host is not a guest user (external users might be blocked)

### Emails Not Arriving

**Check**:
1. **Spam folder** - Graph API emails might be filtered
2. **Email logs** in Microsoft 365 admin center:
   - Go to Exchange admin center
   - Message trace
   - Search for FROM_EMAIL
3. **Transport rules** that might block automated emails
4. **Anti-spam policies** in Microsoft Defender

### Quota Limits

Microsoft Graph API limits:
- **Mail.Send**: 10,000 API requests per 10 minutes per tenant
- **Individual mailbox**: 30 messages per minute

These limits are more than sufficient for visitor management use case.

---

## Security Best Practices

1. **Rotate Secrets Regularly**
   - Set client secret expiration
   - Update before expiry
   - Track expiration dates

2. **Limit Permissions**
   - Only grant `Mail.Send` (already minimal)
   - Don't add unnecessary Graph permissions

3. **Monitor Usage**
   - Review Azure AD sign-in logs regularly
   - Check for unusual API calls
   - Set up alerts for failed auth attempts

4. **Secure Storage**
   - Never commit secrets to git
   - Use Supabase secrets manager
   - Restrict access to secrets

5. **Audit Trail**
   - Enable Azure AD audit logging
   - Review sent emails periodically
   - Monitor notifications table in database

---

## Production Checklist

Before going live:

- [ ] Application registered in Azure AD
- [ ] Client secret created and stored securely
- [ ] Mail.Send permission granted with admin consent
- [ ] FROM_EMAIL mailbox exists and accessible
- [ ] All environment variables configured in Supabase
- [ ] Test email sent and received successfully
- [ ] Reminder cron job scheduled
- [ ] Email templates reviewed and approved
- [ ] Spam folder checked (whitelist sender if needed)
- [ ] Transport rules reviewed (ensure not blocking)
- [ ] Monitoring/alerting set up for failed emails
- [ ] Documentation shared with IT team
- [ ] Client secret expiration date tracked

---

## Alternative: Using SMTP

If you prefer SMTP over Microsoft Graph API:

**Pros:**
- Simpler setup
- Works with any email provider

**Cons:**
- Requires password management
- Less secure than OAuth2
- May have lower rate limits

**Implementation:**
- Would require modifying Edge Function to use SMTP library
- Not currently implemented
- Consider if Graph API doesn't meet requirements

---

## Support Resources

- **Microsoft Graph Documentation**: https://docs.microsoft.com/graph
- **Mail Send API**: https://docs.microsoft.com/graph/api/user-sendmail
- **Azure AD App Registration**: https://docs.microsoft.com/azure/active-directory/develop/quickstart-register-app
- **Microsoft 365 Admin**: https://admin.microsoft.com
- **Azure Portal**: https://portal.azure.com

---

## Summary

Once configured, the email system will:

✅ Send arrival notifications immediately when visitor checks in
✅ Send reminder emails at configured intervals (2h, 4h, 6h, 8h)
✅ Send end-of-day escalation to supervisors for unconfirmed visitors
✅ Send checkout confirmations when requested
✅ All emails include action buttons for one-click host confirmation
✅ Secure token-based links expire after 24 hours
✅ Full audit trail in notifications table

No maintenance required after setup - the system runs automatically!
