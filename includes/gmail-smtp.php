<?php
/**
 * Gmail SMTP Client
 *
 * Sends emails via Gmail SMTP using an App Password.
 * Requires: Gmail account with 2FA enabled + App Password generated.
 * No external libraries required — uses PHP stream sockets.
 */

class GmailSmtpClient
{
    private string $fromEmail;
    private string $fromName;
    private string $appPassword;

    private const SMTP_HOST = 'smtp.gmail.com';
    private const SMTP_PORT = 587;

    public function __construct(string $fromEmail, string $appPassword, string $fromName = '')
    {
        $this->fromEmail = $fromEmail;
        $this->appPassword = $appPassword;
        $this->fromName = $fromName ?: $fromEmail;
    }

    public function getFromEmail(): string
    {
        return $this->fromEmail;
    }

    public function sendEmail(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        return $this->sendSmtp(
            [['email' => $toEmail, 'name' => $toName]],
            $subject,
            $htmlBody
        );
    }

    public function sendEmailToMultiple(array $recipients, string $subject, string $htmlBody): array
    {
        $results = [];
        foreach ($recipients as $recipient) {
            $email = is_array($recipient) ? $recipient['email'] : $recipient;
            $name  = is_array($recipient) ? ($recipient['name'] ?? '') : '';
            try {
                $ok = $this->sendSmtp([['email' => $email, 'name' => $name]], $subject, $htmlBody);
                $results[$email] = ['success' => $ok, 'message' => $ok ? 'sent' : 'failed'];
            } catch (Exception $e) {
                $results[$email] = ['success' => false, 'message' => $e->getMessage()];
            }
        }
        return $results;
    }

    public function verifyConnection(): bool
    {
        $socket = $this->openSocket();
        $this->readResponse($socket, 220);
        $this->sendCommand($socket, 'EHLO gmail-test', 250);
        $this->sendCommand($socket, 'STARTTLS', 220);
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $this->sendCommand($socket, 'EHLO gmail-test', 250);
        $this->authenticate($socket);
        $this->sendCommand($socket, 'QUIT', 221);
        fclose($socket);
        return true;
    }

    private function sendSmtp(array $recipients, string $subject, string $htmlBody): bool
    {
        $socket = $this->openSocket();

        try {
            $this->readResponse($socket, 220);
            $this->sendCommand($socket, 'EHLO gmail-smtp-relay', 250);
            $this->sendCommand($socket, 'STARTTLS', 220);

            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Failed to enable TLS');
            }

            $this->sendCommand($socket, 'EHLO gmail-smtp-relay', 250);
            $this->authenticate($socket);

            $fromFormatted = $this->fromName
                ? '"' . addslashes($this->fromName) . '" <' . $this->fromEmail . '>'
                : $this->fromEmail;

            $this->sendCommand($socket, 'MAIL FROM:<' . $this->fromEmail . '>', 250);

            foreach ($recipients as $recipient) {
                $this->sendCommand($socket, 'RCPT TO:<' . $recipient['email'] . '>', [250, 251]);
            }

            $this->sendCommand($socket, 'DATA', 354);

            $toHeader = implode(', ', array_map(function ($r) {
                return $r['name'] ? '"' . addslashes($r['name']) . '" <' . $r['email'] . '>' : $r['email'];
            }, $recipients));

            $boundary = uniqid('boundary_', true);
            $messageId = '<' . uniqid('msg_', true) . '@sapvisitors.local>';
            $date = date('r');

            $message  = "Date: {$date}\r\n";
            $message .= "Message-ID: {$messageId}\r\n";
            $message .= "From: {$fromFormatted}\r\n";
            $message .= "To: {$toHeader}\r\n";
            $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $message .= "\r\n";
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "\r\n";
            $message .= chunk_split(base64_encode(strip_tags($htmlBody))) . "\r\n";
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "\r\n";
            $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";
            $message .= "--{$boundary}--\r\n";
            $message .= ".\r\n";

            fwrite($socket, $message);
            $this->readResponse($socket, 250);

            $this->sendCommand($socket, 'QUIT', 221);

            return true;
        } finally {
            fclose($socket);
        }
    }

    private function openSocket()
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
            ],
        ]);

        $socket = stream_socket_client(
            'tcp://' . self::SMTP_HOST . ':' . self::SMTP_PORT,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            throw new RuntimeException("SMTP connection failed: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, 30);
        return $socket;
    }

    private function authenticate($socket): void
    {
        $this->sendCommand($socket, 'AUTH LOGIN', 334);
        $this->sendCommand($socket, base64_encode($this->fromEmail), 334);
        $this->sendCommand($socket, base64_encode($this->appPassword), 235);
    }

    private function sendCommand($socket, string $command, $expectedCode): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->readResponse($socket, $expectedCode);
    }

    private function readResponse($socket, $expectedCodes): string
    {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if ($line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        $allowed = is_array($expectedCodes) ? $expectedCodes : [$expectedCodes];

        if (!in_array($code, $allowed)) {
            throw new RuntimeException("SMTP error (expected " . implode('/', $allowed) . ", got {$code}): " . trim($response));
        }

        return $response;
    }
}
