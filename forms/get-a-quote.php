<?php
/**
 * Get A Quote Form Handler using PHPMailer + z.com / cPanel SMTP
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

header('Content-Type: text/plain; charset=UTF-8');

// ============================================
// z.com cPanel → Mail Client Manual Settings
// Secure SSL/TLS (Recommended)
// ============================================
$smtp_host = 'rgl.com.ph'; // Outgoing Server (SMTP)
$smtp_port = 465;         // SMTP Port
$smtp_username = 'info@rgl.com.ph';
$smtp_password = 'EdiEdi1218!@'; // email account password
$receiving_email = 'gil.ballesca@rgl.com.ph';
// ============================================

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

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $smtp_host;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_username;
    $mail->Password = $smtp_password;
    // Port 465 = Secure SSL/TLS (SMTPS)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = $smtp_port;
    $mail->Timeout = 20;
    $mail->CharSet = 'UTF-8';
    // Shared hosting certs sometimes fail strict peer checks
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];

    $mail->setFrom($smtp_username, 'RGL Website Inquiry');
    $mail->Sender = $smtp_username;
    $mail->addAddress($receiving_email);
    // Backup copy so you can verify delivery while testing
    if (strcasecmp($smtp_username, $receiving_email) !== 0) {
        $mail->addBCC($smtp_username);
    }
    $mail->addReplyTo($email, $name);

    $mail->isHTML(false);
    $mail->Subject = $subject;
    $mail->Body = $email_body;

    $mail->send();
    echo 'OK';
} catch (Exception $e) {
    echo "Unable to send email. Error: {$mail->ErrorInfo}";
}
