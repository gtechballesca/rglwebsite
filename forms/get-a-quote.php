<?php
/**
 * Get A Quote Form Handler using PHPMailer + z.com / cPanel
 *
 * Prefers local sendmail (same server → mailbox) so repeat submits do not
 * open a new SMTP TLS session each time. Falls back to SMTP if needed.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');

// ============================================
// SMTP settings (working defaults for rgl.com.ph)
// Optional override: forms/mail-config.php on the server
// ============================================
$smtp_host = 'rgl.com.ph';
$smtp_port = 465;
$smtp_username = 'info@rgl.com.ph';
$smtp_password = 'EdiEdi1218!@';
$receiving_email = 'info@rgl.com.ph';
$from_name = 'RGL Website Inquiry';

$mailConfigPath = __DIR__ . '/mail-config.php';
if (is_file($mailConfigPath)) {
    $mailConfig = require $mailConfigPath;
    if (is_array($mailConfig)) {
        if (!empty($mailConfig['smtp_host'])) {
            $smtp_host = (string) $mailConfig['smtp_host'];
        }
        if (!empty($mailConfig['smtp_port'])) {
            $smtp_port = (int) $mailConfig['smtp_port'];
        }
        if (!empty($mailConfig['smtp_username'])) {
            $smtp_username = (string) $mailConfig['smtp_username'];
        }
        if (!empty($mailConfig['smtp_password']) && $mailConfig['smtp_password'] !== 'YOUR_MAILBOX_PASSWORD') {
            $smtp_password = (string) $mailConfig['smtp_password'];
        }
        if (!empty($mailConfig['receiving_email'])) {
            $receiving_email = (string) $mailConfig['receiving_email'];
        }
        if (!empty($mailConfig['from_name'])) {
            $from_name = (string) $mailConfig['from_name'];
        }
    }
}

if (empty($_POST['name']) || empty($_POST['email'])) {
    echo 'Please fill in all required fields.';
    exit;
}

$name = htmlspecialchars(strip_tags(trim($_POST['name'])));
$email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
$phone = isset($_POST['phone']) ? htmlspecialchars(strip_tags(trim($_POST['phone']))) : '';
$message = isset($_POST['message']) ? htmlspecialchars(strip_tags(trim($_POST['message']))) : '';
$type = isset($_POST['type']) ? htmlspecialchars(strip_tags(trim($_POST['type']))) : '';
$company = isset($_POST['company']) ? htmlspecialchars(strip_tags(trim($_POST['company']))) : '';

if (strlen($name) > 120 || strlen($company) > 160 || strlen($phone) > 40 || strlen($type) > 120 || strlen($message) > 5000) {
    echo 'One or more fields are too long. Please shorten your message and try again.';
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo 'Invalid email format.';
    exit;
}

$subject = '[RGL Website] ' . ($type !== '' ? $type . ' inquiry' : 'Inquiry') . ' from ' . $name;

$email_body = "You have received a new inquiry request from your website.\n\n";
$email_body .= "Name: $name\n";
$email_body .= "Company: $company\n";
$email_body .= "Email: $email\n";
$email_body .= "Phone: $phone\n";
$email_body .= "Inquiry Type: $type\n";
if ($message !== '') {
    $email_body .= "\nMessage:\n$message\n";
}

/**
 * @param string $transport 'sendmail' | 'smtp'
 */
function rgl_build_mailer($transport, $smtp_host, $smtp_port, $smtp_username, $smtp_password)
{
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 20;
    $mail->SMTPKeepAlive = false;

    if ($transport === 'sendmail') {
        $mail->isSendmail();
    } else {
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $smtp_port;
        // Kept for shared-hosting cert quirks; prefer fixing the cert chain when possible
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];
    }

    return $mail;
}

function rgl_send_inquiry($mail, $smtp_username, $receiving_email, $from_name, $email, $name, $subject, $email_body)
{
    $mail->setFrom($smtp_username, $from_name);
    $mail->Sender = $smtp_username;
    $mail->clearAddresses();
    $mail->clearBCCs();
    $mail->clearReplyTos();
    $mail->addAddress($receiving_email);
    if (strcasecmp($smtp_username, $receiving_email) !== 0) {
        $mail->addBCC($smtp_username);
    }
    $mail->addReplyTo($email, $name);
    $mail->isHTML(false);
    $mail->Subject = $subject;
    $mail->Body = $email_body;
    $mail->send();
    if (method_exists($mail, 'smtpClose')) {
        $mail->smtpClose();
    }
}

try {
    $mail = rgl_build_mailer('sendmail', $smtp_host, $smtp_port, $smtp_username, $smtp_password);
    rgl_send_inquiry($mail, $smtp_username, $receiving_email, $from_name, $email, $name, $subject, $email_body);
    echo 'OK';
    exit;
} catch (Exception $e) {
    // fall through to SMTP
}

try {
    $mail = rgl_build_mailer('smtp', $smtp_host, $smtp_port, $smtp_username, $smtp_password);
    rgl_send_inquiry($mail, $smtp_username, $receiving_email, $from_name, $email, $name, $subject, $email_body);
    echo 'OK';
} catch (Exception $e) {
    error_log('RGL get-a-quote mail failed: ' . ($mail->ErrorInfo ?: $e->getMessage()));
    echo 'Unable to send email right now. Please try again or email info@rgl.com.ph.';
}
