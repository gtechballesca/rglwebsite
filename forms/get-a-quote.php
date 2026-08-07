<?php
/**
 * Get A Quote Form Handler using PHPMailer + z.com / cPanel SMTP
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

header('Content-Type: text/plain; charset=UTF-8');

// ============================================
// z.com cPanel SMTP (rglsys@rgl.com.ph)
// Secure SSL/TLS: port 465 + SMTPS
// ============================================
$smtp_host = 'localhost'; // same server as the site — fastest / most reliable on cPanel
$smtp_port = 465;
$smtp_username = 'rglsys@rgl.com.ph';
$smtp_password = 'BmUUQ^oBL4$1I3mi';
$receiving_email = 'info@rgl.com.ph';
// Fallback host if localhost auth fails on your plan: mail.rgl.com.ph
// or cpanel10wh.jpt1.cloud.z.com
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

$subject = 'RGL ' . $type . ' Inquiry from ' . $name;

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
    // Port 465 requires SMTPS (implicit SSL), not STARTTLS
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = $smtp_port;
    $mail->Timeout = 20;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom($smtp_username, 'RGL Website Inquiry');
    $mail->addAddress($receiving_email);
    $mail->addReplyTo($email, $name);

    $mail->isHTML(false);
    $mail->Subject = $subject;
    $mail->Body = $email_body;

    $mail->send();
    echo 'OK';
} catch (Exception $e) {
    echo "Unable to send email. Error: {$mail->ErrorInfo}";
}
