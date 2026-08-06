<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Method not allowed');
}

header('Content-Type: text/plain; charset=UTF-8');

$name = isset($_POST['name']) ? strip_tags(trim((string) $_POST['name'])) : '';
$name = preg_replace('/[\r\n\0]/', '', $name);
$company = isset($_POST['company']) ? strip_tags(trim((string) $_POST['company'])) : '';
$email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
$phone = isset($_POST['phone']) ? strip_tags(trim((string) $_POST['phone'])) : '';
$type = isset($_POST['type']) ? strip_tags(trim((string) $_POST['type'])) : '';
$message = isset($_POST['message']) ? strip_tags(trim((string) $_POST['message'])) : '';

if ($name === '' || $company === '' || $email === '' || $phone === '' || $type === '' || $message === '') {
    http_response_code(400);
    exit('Please fill in all required fields.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit('Please enter a valid email address.');
}

$configFile = __DIR__ . '/mail-config.php';
$config = is_file($configFile) ? require $configFile : [];
if (!is_array($config)) {
    $config = [];
}

$toEmail = (string) ($config['to_email'] ?? 'info@rgl.com.ph');
$fromEmail = (string) ($config['from_email'] ?? 'info@rgl.com.ph');
$fromName = (string) ($config['from_name'] ?? 'RGL Website');

$subject = 'RGL Website Quote Request from ' . $email . ': ' . $type;
$body = "New inquiry from the RGL website\n\n";
$body .= "Name: $name\n";
$body .= "Company: $company\n";
$body .= "Work Email: $email\n";
$body .= "Phone: $phone\n";
$body .= "Service: $type\n\n";
$body .= "Message:\n$message\n";

$logEntry = sprintf(
    "[%s] %s <%s> (%s) - %s\nCompany: %s\n%s\n\n",
    date('c'),
    $name,
    $email,
    $phone,
    $type,
    $company,
    $message
);

function saveSubmissionLog(string $entry): void
{
    @file_put_contents(__DIR__ . '/submissions.log', $entry, FILE_APPEND | LOCK_EX);
}

function web3formsKeyIsSet(array $config): bool
{
    $key = (string) ($config['web3forms_access_key'] ?? '');

    return $key !== '' && $key !== 'YOUR_WEB3FORMS_ACCESS_KEY';
}

function smtpPasswordIsSet(array $config): bool
{
    $password = (string) ($config['smtp_password'] ?? '');

    return $password !== '' && $password !== 'YOUR_CPANEL_EMAIL_PASSWORD_HERE';
}

function sendViaWeb3Forms(string $accessKey, string $subject, string $name, string $email, string $phone, string $company, string $type, string $message): ?string
{
    if (!function_exists('curl_init')) {
        return 'cURL is not available on this server.';
    }

    $payload = json_encode([
        'access_key' => $accessKey,
        'subject' => $subject,
        'from_name' => $name,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'company' => $company,
        'service' => $type,
        'message' => $message,
        'replyto' => $email,
    ]);

    $ch = curl_init('https://api.web3forms.com/submit');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return $curlError !== '' ? $curlError : 'Could not reach Web3Forms.';
    }

    $result = json_decode($response, true);
    if (is_array($result) && !empty($result['success'])) {
        return null;
    }

    return is_array($result) && !empty($result['message'])
        ? (string) $result['message']
        : 'Web3Forms rejected the submission.';
}

function loadPhpMailer(): bool
{
    $phpmailerFile = __DIR__ . '/phpmailer/PHPMailer.php';
    if (!is_file($phpmailerFile)) {
        return false;
    }

    require_once __DIR__ . '/phpmailer/Exception.php';
    require_once __DIR__ . '/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/SMTP.php';

    return true;
}

function sendViaPhpMailer(array $smtpConfig, string $toEmail, string $fromEmail, string $fromName, string $replyEmail, string $replyName, string $subject, string $body): ?string
{
    if (!loadPhpMailer()) {
        return 'PHPMailer is not installed. Upload the forms/phpmailer folder.';
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string) $smtpConfig['smtp_host'];
        $mail->Port = (int) $smtpConfig['smtp_port'];
        
        // Handle Authentication
        if (!empty($smtpConfig['smtp_password'])) {
            $mail->SMTPAuth = true;
            $mail->Username = (string) $smtpConfig['smtp_username'];
            $mail->Password = (string) $smtpConfig['smtp_password'];
        } else {
            $mail->SMTPAuth = false;
        }

        // Security settings for localhost vs external host
        $secure = strtolower((string) ($smtpConfig['smtp_secure'] ?? ''));
        if ($secure === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($secure === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false; // Prevents forced TLS on localhost port 25
        }

        $mail->Timeout = 20;
        $mail->CharSet = PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
        $mail->setFrom($fromEmail, $fromName);
        $mail->Sender = $fromEmail;
        $mail->addAddress($toEmail);
        $mail->addReplyTo($replyEmail, $replyName !== '' ? $replyName : $replyEmail);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();

        return null;
    } catch (PHPMailer\PHPMailer\Exception $exception) {
        return $exception->getMessage();
    }
}


$attempts = [];
$sent = false;
$lastError = '';

if (web3formsKeyIsSet($config)) {
    $error = sendViaWeb3Forms(
        (string) $config['web3forms_access_key'],
        $subject,
        $name,
        $email,
        $phone,
        $company,
        $type,
        $message
    );
    $attempts[] = 'web3forms:' . ($error ?? 'ok');
    if ($error === null) {
        $sent = true;
    } else {
        $lastError = $error;
    }
}

if (!$sent && smtpPasswordIsSet($config)) {
    $smtpProfiles = [
        [
            'label' => 'ssl-465',
            'smtp_host' => (string) ($config['smtp_host'] ?? 'mail.rgl.com.ph'),
            'smtp_port' => (int) ($config['smtp_port'] ?? 465),
            'smtp_secure' => (string) ($config['smtp_secure'] ?? 'ssl'),
            'smtp_username' => (string) ($config['smtp_username'] ?? $fromEmail),
            'smtp_password' => (string) $config['smtp_password'],
        ],
        [
            'label' => 'tls-587',
            'smtp_host' => (string) ($config['smtp_host'] ?? 'mail.rgl.com.ph'),
            'smtp_port' => 587,
            'smtp_secure' => 'tls',
            'smtp_username' => (string) ($config['smtp_username'] ?? $fromEmail),
            'smtp_password' => (string) $config['smtp_password'],
        ],
    ];

    foreach ($smtpProfiles as $profile) {
        $label = (string) $profile['label'];
        unset($profile['label']);
        $error = sendViaPhpMailer($profile, $toEmail, $fromEmail, $fromName, $email, $name, $subject, $body);
        $attempts[] = $label . ':' . ($error ?? 'ok');

        if ($error === null) {
            $sent = true;
            break;
        }

        $lastError = $error;
    }
}

saveSubmissionLog($logEntry . ($sent ? 'SENT VIA: ' : 'SEND FAILED: ') . implode(' | ', $attempts) . "\n\n");

if ($sent) {
    echo 'OK';
    exit;
}

http_response_code(500);

if (!web3formsKeyIsSet($config) && !smtpPasswordIsSet($config)) {
    exit(
        'Email is not configured. In forms/mail-config.php on the server, set either ' .
        'web3forms_access_key (recommended — free at web3forms.com) or smtp_password for info@rgl.com.ph.'
    );
}

exit('Unable to send email: ' . ($lastError ?: 'Delivery failed') . '. Check forms/mail-config.php.');
