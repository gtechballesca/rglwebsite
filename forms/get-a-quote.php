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
// z.com cPanel → Mail Client Manual Settings
// Secure SSL/TLS (Recommended) — used as fallback
// ============================================
$smtp_host = 'rgl.com.ph';
$smtp_port = 465;
$smtp_username = 'info@rgl.com.ph';
$smtp_password = 'EdiEdi1218!@';
$receiving_email = 'info@rgl.com.ph';
// ============================================

$recaptchaConfigPath = __DIR__ . '/recaptcha-config.php';
if (!is_file($recaptchaConfigPath)) {
    echo 'reCAPTCHA is not configured on the server.';
    exit;
}

$recaptchaConfig = require $recaptchaConfigPath;
$recaptchaSecret = isset($recaptchaConfig['secret_key']) ? trim((string) $recaptchaConfig['secret_key']) : '';
$recaptchaMinScore = isset($recaptchaConfig['min_score']) ? (float) $recaptchaConfig['min_score'] : 0.5;
$recaptchaAction = isset($recaptchaConfig['expected_action']) ? (string) $recaptchaConfig['expected_action'] : 'inquiry_submit';

if ($recaptchaSecret === '' || $recaptchaSecret === 'YOUR_RECAPTCHA_SECRET_KEY') {
    echo 'reCAPTCHA secret key is missing. Add it in forms/recaptcha-config.php.';
    exit;
}

/**
 * Verify a reCAPTCHA v3 token with Google.
 *
 * @return array{ok:bool,message:string,score?:float}
 */
function rgl_verify_recaptcha($token, $secret, $minScore, $expectedAction)
{
    if ($token === '') {
        return ['ok' => false, 'message' => 'reCAPTCHA verification failed. Please refresh and try again.'];
    }

    $payload = http_build_query([
        'secret' => $secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);

    $raw = false;
    if (function_exists('curl_init')) {
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 10,
            ],
        ]);
        $raw = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    }

    if ($raw === false || $raw === '') {
        return ['ok' => false, 'message' => 'Unable to verify reCAPTCHA. Please try again shortly.'];
    }

    $result = json_decode($raw, true);
    if (!is_array($result) || empty($result['success'])) {
        return ['ok' => false, 'message' => 'reCAPTCHA verification failed. Please refresh and try again.'];
    }

    if (isset($result['action']) && $result['action'] !== $expectedAction) {
        return ['ok' => false, 'message' => 'reCAPTCHA action mismatch. Submission blocked.'];
    }

    $score = isset($result['score']) ? (float) $result['score'] : 0.0;
    if ($score < $minScore) {
        return ['ok' => false, 'message' => 'Submission blocked by spam protection. Please try again later.', 'score' => $score];
    }

    return ['ok' => true, 'message' => 'OK', 'score' => $score];
}

$recaptchaToken = '';
if (!empty($_POST['recaptcha-response'])) {
    $recaptchaToken = trim((string) $_POST['recaptcha-response']);
} elseif (!empty($_POST['g-recaptcha-response'])) {
    $recaptchaToken = trim((string) $_POST['g-recaptcha-response']);
}

$recaptchaCheck = rgl_verify_recaptcha($recaptchaToken, $recaptchaSecret, $recaptchaMinScore, $recaptchaAction);
if (!$recaptchaCheck['ok']) {
    echo $recaptchaCheck['message'];
    exit;
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
 * Build a configured PHPMailer instance for the given transport.
 *
 * @param string $transport 'sendmail' | 'smtp'
 */
function rgl_build_mailer($transport, $smtp_host, $smtp_port, $smtp_username, $smtp_password)
{
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 20;
    $mail->SMTPKeepAlive = false;

    if ($transport === 'sendmail') {
        // Local MTA — best for same-domain delivery on cPanel / z.com
        $mail->isSendmail();
    } else {
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $smtp_port;
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

function rgl_send_inquiry($mail, $smtp_username, $receiving_email, $email, $name, $subject, $email_body)
{
    $mail->setFrom($smtp_username, 'RGL Website Inquiry');
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

$lastError = '';

try {
    $mail = rgl_build_mailer('sendmail', $smtp_host, $smtp_port, $smtp_username, $smtp_password);
    rgl_send_inquiry($mail, $smtp_username, $receiving_email, $email, $name, $subject, $email_body);
    echo 'OK';
    exit;
} catch (Exception $e) {
    $lastError = $mail->ErrorInfo ?: $e->getMessage();
}

try {
    $mail = rgl_build_mailer('smtp', $smtp_host, $smtp_port, $smtp_username, $smtp_password);
    rgl_send_inquiry($mail, $smtp_username, $receiving_email, $email, $name, $subject, $email_body);
    echo 'OK';
} catch (Exception $e) {
    $detail = $mail->ErrorInfo ?: $e->getMessage();
    if ($lastError !== '') {
        echo "Unable to send email. Error: {$detail} (sendmail: {$lastError})";
    } else {
        echo "Unable to send email. Error: {$detail}";
    }
}
