<?php
/**
 * Microsoft Graph API Client
 *
 * Handles authentication and email sending via Microsoft Graph API
 * using OAuth2 client credentials flow.
 */

class MicrosoftGraphClient
{
    private string $tenantId;
    private string $clientId;
    private string $clientSecret;
    private string $fromEmail;
    private string $fromName;
    private ?string $accessToken = null;
    private ?int $tokenExpiresAt = null;

    private const TOKEN_ENDPOINT = 'https://login.microsoftonline.com/{tenantId}/oauth2/v2.0/token';
    private const GRAPH_ENDPOINT = 'https://graph.microsoft.com/v1.0';

    /**
     * Constructor
     *
     * @param string $tenantId Azure AD Tenant ID
     * @param string $clientId Application (client) ID
     * @param string $clientSecret Client secret
     * @param string $fromEmail Sender email address (must be from verified domain)
     * @param string $fromName Sender display name
     */
    public function __construct(
        string $tenantId,
        string $clientId,
        string $clientSecret,
        string $fromEmail,
        string $fromName = 'SAP Visitor System'
    ) {
        $this->tenantId = $tenantId;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    /**
     * Get access token for Microsoft Graph API
     *
     * @return string Access token
     * @throws Exception If authentication fails
     */
    public function getAccessToken(): string
    {
        // Return cached token if still valid
        if ($this->accessToken !== null && $this->tokenExpiresAt !== null) {
            if (time() < $this->tokenExpiresAt - 300) { // 5 min buffer
                return $this->accessToken;
            }
        }

        $tokenUrl = str_replace('{tenantId}', $this->tenantId, self::TOKEN_ENDPOINT);

        $postData = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'https://graph.microsoft.com/.default'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('Curl error during authentication: ' . $curlError);
        }

        if ($httpCode !== 200) {
            throw new Exception('Authentication failed with HTTP code: ' . $httpCode . ', Response: ' . $response);
        }

        $data = json_decode($response, true);

        if (!isset($data['access_token'])) {
            throw new Exception('Invalid authentication response: ' . $response);
        }

        $this->accessToken = $data['access_token'];
        $this->tokenExpiresAt = time() + ($data['expires_in'] ?? 3600);

        return $this->accessToken;
    }

    /**
     * Send email via Microsoft Graph API
     *
     * @param string $toEmail Recipient email address
     * @param string $toName Recipient name (optional)
     * @param string $subject Email subject
     * @param string $htmlBody HTML email body
     * @param string $textBody Plain text body (optional, for fallback)
     * @return array Response from Graph API
     * @throws Exception If sending fails
     */
    public function sendEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody = ''
    ): array {
        $accessToken = $this->getAccessToken();

        // Build email message
        $message = [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $htmlBody
                ],
                'from' => [
                    'emailAddress' => [
                        'address' => $this->fromEmail,
                        'name' => $this->fromName
                    ]
                ],
                'toRecipients' => [
                    [
                        'emailAddress' => [
                            'address' => $toEmail,
                            'name' => $toName ?: $toEmail
                        ]
                    ]
                ]
            ],
            'saveToSentItems' => true
        ];

        // Add text body as alternative if provided
        if (!empty($textBody)) {
            $message['message']['body']['contentType'] = 'HTML';
            // Note: Graph API doesn't support multipart/alternative directly
            // The HTML body should be self-contained
        }

        $sendUrl = self::GRAPH_ENDPOINT . '/users/' . urlencode($this->fromEmail) . '/sendMail';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $sendUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('Curl error sending email: ' . $curlError);
        }

        // Graph API returns 202 Accepted on success with no body
        if ($httpCode === 202) {
            return [
                'success' => true,
                'messageId' => null, // Graph doesn't return message ID on send
                'httpCode' => $httpCode
            ];
        }

        // Error response
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
        $errorCode = $errorData['error']['code'] ?? 'Unknown';

        throw new Exception(
            sprintf('Email sending failed: [%s] %s (HTTP %d)', $errorCode, $errorMessage, $httpCode)
        );
    }

    /**
     * Send email to multiple recipients
     *
     * @param array $recipients Array of ['email' => '...', 'name' => '...']
     * @param string $subject Email subject
     * @param string $htmlBody HTML email body
     * @param string $textBody Plain text body (optional)
     * @return array Results for each recipient
     */
    public function sendEmailToMultiple(
        array $recipients,
        string $subject,
        string $htmlBody,
        string $textBody = ''
    ): array {
        $results = [];

        foreach ($recipients as $recipient) {
            $email = $recipient['email'] ?? $recipient;
            $name = $recipient['name'] ?? '';

            try {
                $result = $this->sendEmail($email, $name, $subject, $htmlBody, $textBody);
                $results[$email] = ['success' => true, 'data' => $result];
            } catch (Exception $e) {
                $results[$email] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Verify connection and credentials
     *
     * @return bool True if connection successful
     * @throws Exception If verification fails
     */
    public function verifyConnection(): bool
    {
        try {
            $accessToken = $this->getAccessToken();

            // Try to get user info to verify
            $url = self::GRAPH_ENDPOINT . '/users/' . urlencode($this->fromEmail);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                return true;
            }

            throw new Exception('User verification failed with HTTP code: ' . $httpCode);
        } catch (Exception $e) {
            throw new Exception('Connection verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Get sender email address
     *
     * @return string
     */
    public function getFromEmail(): string
    {
        return $this->fromEmail;
    }

    /**
     * Get sender name
     *
     * @return string
     */
    public function getFromName(): string
    {
        return $this->fromName;
    }
}
