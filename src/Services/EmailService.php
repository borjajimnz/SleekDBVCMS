<?php

namespace SleekDBVCMS\Services;

/**
 * Minimal SMTP client used to notify by email when a lead_form is submitted.
 *
 * Reads its configuration from the dashboard site settings (see
 * ConfigurationService::DEFAULT_SETTINGS). When SMTP is not enabled or no host
 * is configured, send() returns false and the lead is only stored in the
 * "leads" collection.
 */
class EmailService
{
    private array $settings;
    private Logger $logger;

    public function __construct(array $settings, Logger $logger)
    {
        $this->settings = $settings;
        $this->logger = $logger;
    }

    public function isConfigured(): bool
    {
        return !empty($this->settings['smtp_enabled'])
            && !empty($this->settings['smtp_host']);
    }

    /**
     * Send an HTML email. Returns true on success, false otherwise.
     *
     * @param string $to      Recipient (as configured on the lead_form).
     * @param string $subject
     * @param string $body    HTML body.
     * @param string $cc      Comma separated CC recipients (may be empty).
     * @param string $replyTo Optional Reply-To (usually the lead's email).
     */
    public function send(string $to, string $subject, string $body, string $cc = '', string $replyTo = ''): bool
    {
        if (!$this->isConfigured()) {
            $this->logger->log('EmailService: SMTP not configured, lead email skipped.');
            return false;
        }

        $host = trim((string)$this->settings['smtp_host']);
        $port = (int)($this->settings['smtp_port'] ?: 587);
        $user = (string)($this->settings['smtp_username'] ?? '');
        $pass = (string)($this->settings['smtp_password'] ?? '');
        $encryption = strtolower((string)($this->settings['smtp_encryption'] ?? 'tls'));
        $fromEmail = trim((string)($this->settings['smtp_from_email'] ?? ''));
        $fromName = trim((string)($this->settings['smtp_from_name'] ?? ''));
        if ($fromEmail === '') {
            $fromEmail = $user;
        }

        $socketHost = $host . ':' . $port;
        $remote = $socketHost;
        if ($encryption === 'ssl') {
            $remote = 'ssl://' . $socketHost;
        }

        $errno = 0;
        $errstr = '';
        $conn = @stream_socket_client(
            $remote,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            stream_context_create([
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ])
        );
        if (!$conn) {
            $this->logger->log("EmailService: connection to {$host}:{$port} failed: {$errstr} ({$errno})");
            return false;
        }

        stream_set_timeout($conn, 30);

        if (!$this->expect($conn, '220')) {
            $this->logger->log('EmailService: no greeting from server.');
            fclose($conn);
            return false;
        }

        if (!$this->command($conn, "EHLO " . $this->heloHost(), '250')) {
            fclose($conn);
            return false;
        }

        // STARTTLS upgrade when configured (implicit SSL was handled above).
        if ($encryption === 'tls') {
            if (!$this->command($conn, "STARTTLS", '220')) {
                fclose($conn);
                return false;
            }
            $ok = stream_socket_enable_crypto($conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$ok) {
                $this->logger->log('EmailService: STARTTLS handshake failed.');
                fclose($conn);
                return false;
            }
            if (!$this->command($conn, "EHLO " . $this->heloHost(), '250')) {
                fclose($conn);
                return false;
            }
        }

        // Auth only if a username was supplied.
        if ($user !== '') {
            if (!$this->command($conn, "AUTH LOGIN", '334')) {
                fclose($conn);
                return false;
            }
            if (!$this->command($conn, base64_encode($user), '334')) {
                fclose($conn);
                return false;
            }
            if (!$this->command($conn, base64_encode($pass), '235')) {
                fclose($conn);
                return false;
            }
        }

        if (!$this->command($conn, "MAIL FROM:<{$fromEmail}>", '250')) {
            fclose($conn);
            return false;
        }

        $recipients = array_unique(array_filter(array_map('trim', explode(',', $to . ',' . $cc))));
        if (empty($recipients)) {
            $this->logger->log('EmailService: no recipients.');
            fclose($conn);
            return false;
        }
        foreach ($recipients as $rcpt) {
            if ($rcpt === '') {
                continue;
            }
            if (!$this->command($conn, "RCPT TO:<{$rcpt}>", ['250', '251'])) {
                fclose($conn);
                return false;
            }
        }

        if (!$this->command($conn, "DATA", '354')) {
            fclose($conn);
            return false;
        }

        $headers = $this->buildHeaders($fromEmail, $fromName, $to, $cc, $subject, $replyTo);
        $message = $headers . "\r\n" . $body . "\r\n.\r\n";

        // Prevent lone-dot / CRLF injection from the message.
        $message = str_replace("\r\n.\r\n", "\r\n..\r\n", $message);

        if (fwrite($conn, $message) === false) {
            fclose($conn);
            return false;
        }
        if (!$this->expect($conn, '250')) {
            fclose($conn);
            return false;
        }

        $this->command($conn, "QUIT", '221');
        fclose($conn);
        return true;
    }

    private function heloHost(): string
    {
        $host = $_SERVER['SERVER_NAME'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $host = preg_replace('/^[\w]+:\/\//', '', (string)$host);
        return preg_replace('/[^a-zA-Z0-9.\-]/', '', $host) ?: 'localhost';
    }

    private function buildHeaders(string $fromEmail, string $fromName, string $to, string $cc, string $subject, string $replyTo): string
    {
        $headers = '';
        if ($fromName !== '') {
            $headers .= "From: {$this->encodeHeader($fromName)} <{$fromEmail}>\r\n";
        } else {
            $headers .= "From: {$fromEmail}\r\n";
        }
        if ($to !== '') {
            $headers .= "To: {$to}\r\n";
        }
        if ($cc !== '') {
            $headers .= "Cc: {$cc}\r\n";
        }
        if ($replyTo !== '') {
            $headers .= "Reply-To: {$replyTo}\r\n";
        }
        $headers .= "Subject: {$this->encodeHeader($subject)}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";
        $headers .= 'X-Mailer: SleekDBVCMS' . "\r\n";
        $headers .= "\r\n";
        return $headers;
    }

    private function encodeHeader(string $value): string
    {
        $value = str_replace(["\r", "\n"], '', $value);
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }

    private function command($conn, string $cmd, $expected): bool
    {
        if (fwrite($conn, $cmd . "\r\n") === false) {
            return false;
        }
        return $this->expect($conn, $expected);
    }

    private function expect($conn, $expected): bool
    {
        $expectedCodes = is_array($expected) ? $expected : [$expected];
        $line = $this->readLine($conn);
        if ($line === false) {
            return false;
        }
        // A response may span multiple lines; the final one has a space after the code.
        while (isset($line[3]) && $line[3] === '-') {
            $line = $this->readLine($conn);
            if ($line === false) {
                return false;
            }
        }
        $code = substr($line, 0, 3);
        return in_array($code, $expectedCodes, true);
    }

    private function readLine($conn): ?string
    {
        $line = fgets($conn, 515);
        if ($line === false) {
            return null;
        }
        return rtrim($line, "\r\n");
    }
}
