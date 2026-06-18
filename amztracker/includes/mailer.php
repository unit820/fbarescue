<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/Exception.php';

function sendUpdateEmail($toEmail, $toName, $accountName, $status, $progress, $note = '') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your@gmail.com';      // <-- change
        $mail->Password   = 'your_app_password';    // <-- change
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('your@gmail.com', 'Amazon Tracker');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = "Update on your account: $accountName";
        $mail->Body    = "
            <h2>Account Update</h2>
            <p>Hello <b>$toName</b>,</p>
            <p>Your account <b>$accountName</b> has been updated:</p>
            <ul>
                <li>Status: <b>$status</b></li>
                <li>Progress: <b>$progress%</b></li>
                " . ($note ? "<li>Note: $note</li>" : "") . "
            </ul>
            <p>Login to view full details.</p>
        ";
        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer error: {$mail->ErrorInfo}");
    }
}
