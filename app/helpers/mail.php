<?php
// app/helpers/mail.php
// Lightweight mail helper using PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!function_exists('mail_load_dependencies')) {
    function mail_load_dependencies() {
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }
    }
}

if (!function_exists('mailer_instance')) {
    function mailer_instance(): ?PHPMailer {
        // Allow disabling email entirely (prevents request blocking when SMTP unreachable). Default is disabled.
        $enabled = $_ENV['MAIL_ENABLED'] ?? 'false';
        if (strtolower($enabled) === 'false' || $enabled === '0') {
            return null;
        }
        mail_load_dependencies();
        if (!class_exists(PHPMailer::class)) {
            error_log('PHPMailer not installed. Run composer install');
            return null;
        }
        $mail = new PHPMailer(true);
        $host = $_ENV['MAIL_HOST'] ?? 'localhost';
        $port = (int)($_ENV['MAIL_PORT'] ?? 25);
        $user = $_ENV['MAIL_USERNAME'] ?? null;
        $pass = $_ENV['MAIL_PASSWORD'] ?? null;
        $secure = $_ENV['MAIL_ENCRYPTION'] ?? '';
        $from = $_ENV['MAIL_FROM_ADDRESS'] ?? $_ENV['MAIL_FROM'] ?? 'no-reply@example.com';
        $fromName = $_ENV['MAIL_FROM_NAME'] ?? 'CampusLite ERP';
        $timeout = (int)($_ENV['MAIL_TIMEOUT'] ?? 5); // seconds

        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->Timeout = $timeout;
        $mail->SMTPDebug = 0;
        $mail->SMTPAuth = !empty($user);
        if ($mail->SMTPAuth) {
            $mail->Username = $user;
            $mail->Password = $pass;
        }
        // Auto-detect SSL for port 465, TLS for others
        if (!empty($secure)) {
            if ($port == 465 && strtolower($secure) === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // ssl for port 465
            } else {
                $mail->SMTPSecure = $secure; // tls or ssl
            }
        }
        $mail->SMTPAutoTLS = false; // Disable auto TLS if explicit encryption set
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        $mail->setFrom($from, $fromName);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        return $mail;
    }
}

if (!function_exists('render_template')) {
    function render_template(string $templatePath, array $vars = []): string {
        if (!file_exists($templatePath)) return '';
        $html = file_get_contents($templatePath);
        $replacements = [];
        foreach ($vars as $k => $v) {
            $replacements['{{' . $k . '}}'] = (string)$v;
        }
        return strtr($html, $replacements);
    }
}

if (!function_exists('send_mail_message')) {
    /**
     * Send a single email
     * @param array $opts ['to'=>[['email','name']], 'subject','html','alt']
     */
    function send_mail_message(array $opts): bool {
        $mail = mailer_instance();
        if (!$mail) return false;
        try {
            $recipients = $opts['to'] ?? [];
            if (empty($recipients)) return false;
            foreach ($recipients as $rcpt) {
                if (is_array($rcpt)) {
                    $mail->addAddress($rcpt['email'] ?? '', $rcpt['name'] ?? '');
                } else {
                    $mail->addAddress((string)$rcpt);
                }
            }
            $mail->Subject = $opts['subject'] ?? '';
            $mail->Body = $opts['html'] ?? '';
            $mail->AltBody = $opts['alt'] ?? strip_tags($mail->Body);
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Mail send failed: ' . $e->getMessage());
            return false;
        }
    }
}
