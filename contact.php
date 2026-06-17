<?php
/**
 * contact.php — Seller Rescue Contact Form
 * Fixed version: proper PHPMailer use, confirmation email to user, all bugs resolved.
 *
 * SETUP INSTRUCTIONS:
 * 1. Download PHPMailer from https://github.com/PHPMailer/PHPMailer
 * 2. Place Exception.php, PHPMailer.php, SMTP.php inside a folder called: phpmailer/
 *    So paths are: phpmailer/Exception.php, phpmailer/PHPMailer.php, phpmailer/SMTP.php
 * 3. In Google Account → Security → 2-Step Verification → App Passwords
 *    Generate a 16-character App Password for "Mail"
 * 4. Replace YOUR_APP_PASSWORD below with that password
 */

// ── FIX #1: use statements MUST be at the top, outside any function/if block ──
require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ── SESSION + CSRF ──────────────────────────────────────────────────────────
session_start();

$ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_key = 'rate_' . md5($ip);

if (!isset($_SESSION[$rate_key])) {
    $_SESSION[$rate_key] = ['count' => 0, 'reset_time' => time() + 3600];
}
if (time() > $_SESSION[$rate_key]['reset_time']) {
    $_SESSION[$rate_key] = ['count' => 0, 'reset_time' => time() + 3600];
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ── SMTP CREDENTIALS (edit here only) ──────────────────────────────────────
define('SMTP_USER', 'syedgillani088@gmail.com');
define('SMTP_PASS', 'vrcezitzgellccmx');   // ← Replace with your App Password
define('OWNER_NAME', 'Syed Hassan Bacha');

// ── FORM PROCESSING ─────────────────────────────────────────────────────────
$form_result = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Rate limit check
    if ($_SESSION[$rate_key]['count'] >= 5) {
        $form_result['message'] = 'Too many submissions. Please try again later.';
    }
    // Honeypot check (silent pass for bots)
    elseif (!empty($_POST['website'])) {
        $form_result['success'] = true;
    }
    // CSRF check
    elseif (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $form_result['message'] = 'Security token mismatch. Please refresh and try again.';
    }
    else {
        // Sanitize inputs
        $name    = htmlspecialchars(trim($_POST['name']    ?? ''), ENT_QUOTES, 'UTF-8');
        $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $phone   = htmlspecialchars(trim($_POST['phone']   ?? ''), ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars(trim($_POST['subject'] ?? ''), ENT_QUOTES, 'UTF-8');
        // FIX #4: sanitize AFTER nl2br so line breaks work correctly in email
        $message_raw      = trim($_POST['message'] ?? '');
        $message_display  = nl2br(htmlspecialchars($message_raw, ENT_QUOTES, 'UTF-8'));
        $terms            = isset($_POST['terms']) && $_POST['terms'] === '1';

        // Validation
        $errors = [];
        if (empty($name))              $errors[] = 'Name is required.';
        if (!$email)                   $errors[] = 'Valid email is required.';
        if (empty($subject))           $errors[] = 'Subject is required.';
        if (strlen($message_raw) < 10) $errors[] = 'Message must be at least 10 characters.';
        if (!$terms)                   $errors[] = 'You must accept the Terms of Service.';

        if (!empty($errors)) {
            $form_result['message'] = implode(' ', $errors);
        } else {

            // ── Helper: build mailer instance ─────────────────────────────
            function makeMailer(): PHPMailer {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';
                return $mail;
            }

            $send_errors = [];

            // ── EMAIL 1: Notification to YOU (site owner) ─────────────────
            try {
                $mail = makeMailer();
                $mail->setFrom(SMTP_USER, 'Seller Rescue Contact Form');
                $mail->addAddress(SMTP_USER, OWNER_NAME);
                $mail->addReplyTo($email, $name);
                $mail->isHTML(true);
                $mail->Subject = 'New Contact: ' . $subject . ' — Seller Rescue';
                $mail->Body = '
                <html><body style="font-family:Arial,sans-serif;color:#1a1a2e;background:#f9fafb;padding:20px">
                <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.1)">
                  <div style="background:#0A192F;padding:24px 30px">
                    <h2 style="color:#F4A900;margin:0;font-size:1.3rem">New Message — Seller Rescue</h2>
                  </div>
                  <div style="padding:30px">
                    <table style="width:100%;border-collapse:collapse;font-size:.9rem">
                      <tr><td style="padding:8px 0;color:#6b7280;width:120px">Name</td><td style="padding:8px 0;font-weight:600">' . $name . '</td></tr>
                      <tr><td style="padding:8px 0;color:#6b7280">Email</td><td style="padding:8px 0"><a href="mailto:' . $email . '" style="color:#0A192F">' . $email . '</a></td></tr>
                      <tr><td style="padding:8px 0;color:#6b7280">Phone</td><td style="padding:8px 0">' . ($phone ?: '—') . '</td></tr>
                      <tr><td style="padding:8px 0;color:#6b7280">Subject</td><td style="padding:8px 0;font-weight:600;color:#F4A900">' . $subject . '</td></tr>
                    </table>
                    <hr style="border:none;border-top:1px solid #e5e7eb;margin:20px 0">
                    <p style="color:#6b7280;font-size:.85rem;margin-bottom:.5rem">Message:</p>
                    <p style="line-height:1.8;color:#1a1a2e">' . $message_display . '</p>
                  </div>
                  <div style="background:#f9fafb;padding:16px 30px;font-size:.75rem;color:#9ca3af">
                    Sent via sellerrescue.com contact form | IP: ' . htmlspecialchars($ip) . '
                  </div>
                </div></body></html>';
                $mail->AltBody = "Name: $name\nEmail: $email\nPhone: $phone\nSubject: $subject\n\n$message_raw";
                $mail->send();
            } catch (Exception $e) {
                $send_errors[] = 'owner';
                error_log('SellerRescue - Owner email failed: ' . $e->getMessage());
            }

            // ── FIX #2: EMAIL 2 — Confirmation email TO THE USER ──────────
            try {
                $mail2 = makeMailer();
                $mail2->setFrom(SMTP_USER, OWNER_NAME . ' — Seller Rescue');
                $mail2->addAddress($email, $name);
                $mail2->isHTML(true);
                $mail2->Subject = 'We received your message — Seller Rescue';
                $mail2->Body = '
                <html><body style="font-family:Arial,sans-serif;color:#1a1a2e;background:#f9fafb;padding:20px">
                <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.1)">
                  <div style="background:#0A192F;padding:24px 30px">
                    <h2 style="color:#F4A900;margin:0;font-size:1.3rem">Thank You, ' . $name . '!</h2>
                  </div>
                  <div style="padding:30px">
                    <p style="line-height:1.8;margin-bottom:1.2rem">
                      Thank you for reaching out to <strong>Seller Rescue</strong>. I have received your message regarding
                      <strong style="color:#F4A900">' . $subject . '</strong> and will get back to you within <strong>24 hours</strong>.
                    </p>
                    <p style="line-height:1.8;margin-bottom:1.2rem">
                      If your matter is urgent, you can also reach me directly:
                    </p>
                    <table style="width:100%;border-collapse:collapse;font-size:.9rem;margin-bottom:1.5rem">
                      <tr><td style="padding:6px 0;color:#6b7280;width:120px">📧 Email</td><td><a href="mailto:' . SMTP_USER . '" style="color:#0A192F">' . SMTP_USER . '</a></td></tr>
                      <tr><td style="padding:6px 0;color:#6b7280">📱 WhatsApp</td><td><a href="tel:+923439737494" style="color:#0A192F">+92 343 9737494</a></td></tr>
                    </table>
                    <hr style="border:none;border-top:1px solid #e5e7eb;margin:20px 0">
                    <p style="color:#6b7280;font-size:.85rem;margin-bottom:.4rem">Your message summary:</p>
                    <p style="line-height:1.8;color:#1a1a2e;font-size:.88rem;background:#f9fafb;padding:1rem;border-radius:8px">' . $message_display . '</p>
                  </div>
                  <div style="background:#f9fafb;padding:16px 30px;font-size:.75rem;color:#9ca3af">
                    © 2025 Seller Rescue | sellerrescue.com
                  </div>
                </div></body></html>';
                $mail2->AltBody = "Hi $name,\n\nThank you for contacting Seller Rescue. I've received your message about \"$subject\" and will reply within 24 hours.\n\nYour message:\n$message_raw\n\n— " . OWNER_NAME;
                $mail2->send();
            } catch (Exception $e) {
                $send_errors[] = 'user';
                error_log('SellerRescue - User confirmation email failed: ' . $e->getMessage());
            }

            // ── FIX #5: Only count + redirect if at least owner email sent ─
            if (!in_array('owner', $send_errors)) {
                $_SESSION[$rate_key]['count']++;
                $form_result['success'] = true;
                header('Location: thank-you.php');
                exit;
            } else {
                $form_result['message'] = 'Email could not be sent. Please email directly at <a href="mailto:' . SMTP_USER . '">' . SMTP_USER . '</a>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Contact | Seller Rescue — Syed Hassan Bacha</title>
<meta name="description" content="Contact Syed Hassan Bacha for expert Amazon reinstatement and legal services.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#0A192F;--gold:#F4A900;--white:#FFFFFF;--off-white:#F9FAFB;--charcoal:#1A1A2E;--gray:#6B7280;--gray-light:#E5E7EB;--radius:12px;--container:1200px}
body{font-family:'DM Sans',sans-serif;background:var(--off-white);color:var(--charcoal)}
h1,h2,h3{font-family:'Syne',sans-serif;font-weight:700}
.container{max-width:var(--container);margin:0 auto;padding:0 1.5rem}
nav{background:var(--navy);padding:1.1rem 0}
.nav-inner{display:flex;align-items:center;justify-content:space-between;max-width:var(--container);margin:0 auto;padding:0 1.5rem}
.nav-logo{font-family:'Syne',sans-serif;font-weight:800;font-size:1.35rem;color:#fff;text-decoration:none}
.nav-logo span{color:var(--gold)}
.nav-links{display:flex;gap:2rem;list-style:none}
.nav-links a{color:rgba(255,255,255,.75);text-decoration:none;font-size:.9rem;transition:color .2s}
.nav-links a:hover{color:var(--gold)}
.page-header{background:var(--navy);padding:3.5rem 0;text-align:center}
.page-header h1{color:#fff;font-size:clamp(2rem,4vw,3rem);margin-bottom:.5rem}
.page-header p{color:rgba(255,255,255,.5);font-size:1rem}
.breadcrumb{display:flex;align-items:center;justify-content:center;gap:.5rem;font-size:.8rem;color:rgba(255,255,255,.4);margin-bottom:1rem}
.breadcrumb a{color:var(--gold);text-decoration:none}
.contact-section{padding:5rem 0}
.contact-grid{display:grid;grid-template-columns:1fr 1.6fr;gap:3rem;align-items:start}
.contact-info-panel{background:var(--navy);border-radius:var(--radius);padding:2.5rem;color:#fff}
.contact-info-panel h2{font-size:1.5rem;margin-bottom:.5rem}
.contact-info-panel>p{color:rgba(255,255,255,.5);font-size:.9rem;margin-bottom:2rem}
.info-item{display:flex;gap:1rem;align-items:flex-start;margin-bottom:1.8rem}
.info-icon{width:44px;height:44px;min-width:44px;background:rgba(244,169,0,.12);border:1px solid rgba(244,169,0,.25);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem}
.info-text strong{display:block;color:#fff;font-size:.88rem;margin-bottom:.15rem}
.info-text a,.info-text span{color:rgba(255,255,255,.5);font-size:.85rem;text-decoration:none}
.form-panel{background:#fff;border-radius:var(--radius);padding:2.5rem;box-shadow:0 4px 30px rgba(0,0,0,.06)}
.form-panel h2{font-size:1.5rem;margin-bottom:.3rem}
.form-panel>p{color:var(--gray);font-size:.88rem;margin-bottom:1.5rem}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.field{margin-bottom:1.2rem}
.field label{display:block;font-size:.83rem;font-weight:500;margin-bottom:.4rem}
.req{color:var(--gold)}
.field input,.field select,.field textarea{width:100%;padding:.7rem 1rem;border:1.5px solid var(--gray-light);border-radius:8px;font-family:'DM Sans',sans-serif;font-size:.9rem;color:var(--charcoal);background:var(--off-white);outline:none;transition:border-color .2s}
.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--gold);background:#fff}
.field textarea{resize:vertical;min-height:130px}
.field select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 1rem center;cursor:pointer}
.checkbox-field{display:flex;align-items:flex-start;gap:.6rem;margin-bottom:1.5rem;font-size:.83rem;color:var(--gray)}
.checkbox-field input{min-width:18px;height:18px;margin-top:.15rem;accent-color:var(--gold)}
.checkbox-field a{color:var(--gold);text-decoration:none}
.submit-btn{width:100%;background:var(--navy);color:#fff;border:none;padding:.85rem 2rem;border-radius:8px;font-family:'Syne',sans-serif;font-size:1rem;font-weight:600;cursor:pointer;transition:all .25s}
.submit-btn:hover{background:var(--gold);color:var(--navy)}
.alert{padding:1rem 1.2rem;border-radius:8px;font-size:.88rem;margin-bottom:1.2rem}
.alert-error{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
footer{background:#1A1A2E;color:rgba(255,255,255,.4);padding:1.5rem 0;text-align:center;font-size:.8rem;margin-top:3rem}
footer a{color:var(--gold);text-decoration:none}
@media(max-width:900px){.contact-grid{grid-template-columns:1fr}}
@media(max-width:768px){.nav-links{display:none}.form-row{grid-template-columns:1fr}}
</style>
</head>
<body>

<nav>
  <div class="nav-inner">
    <a href="index.php" class="nav-logo">SELLER <span>RESCUE</span></a>
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="index.php#about">About</a></li>
      <li><a href="index.php#services">Services</a></li>
      <li><a href="contact.php" style="color:var(--gold)">Contact</a></li>
    </ul>
  </div>
</nav>

<div class="page-header">
  <div class="breadcrumb"><a href="index.php">Home</a> / Contact</div>
  <h1>Get In Touch</h1>
  <p>Let's discuss your Amazon issue and find the best solution.</p>
</div>

<section class="contact-section">
  <div class="container">
    <div class="contact-grid">
      <div class="contact-info-panel">
        <h2>Contact Information</h2>
        <p>Reach out through any of the channels below. I respond within 24 hours.</p>
        <div class="info-item">
          <div class="info-icon">📧</div>
          <div class="info-text"><strong>Email</strong><a href="mailto:syedgillani088@gmail.com">syedgillani088@gmail.com</a></div>
        </div>
        <div class="info-item">
          <div class="info-icon">📱</div>
          <div class="info-text"><strong>Phone / WhatsApp</strong><a href="tel:+923439737494">+92 343 9737494</a></div>
        </div>
        <div class="info-item">
          <div class="info-icon">⏰</div>
          <div class="info-text"><strong>Working Hours</strong><span>Mon–Sat: 9:00 AM – 8:00 PM (PKT)</span></div>
        </div>
        <div class="info-item">
          <div class="info-icon">📍</div>
          <div class="info-text"><strong>Location</strong><span>Pakistan (Remote Services Worldwide)</span></div>
        </div>
      </div>

      <div class="form-panel">
        <h2>Send a Message</h2>
        <p>Fill in the form and I'll get back to you within 24 hours.</p>

        <?php if (!empty($form_result['message'])): ?>
          <div class="alert alert-error">❌ <?= $form_result['message'] ?></div>
        <?php endif; ?>

        <form method="POST" action="contact.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

          <div class="form-row">
            <div class="field">
              <label>Full Name <span class="req">*</span></label>
              <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="field">
              <label>Email Address <span class="req">*</span></label>
              <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label>Phone Number</label>
              <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
            <div class="field">
              <label>Subject <span class="req">*</span></label>
              <select name="subject" required>
                <option value="" disabled <?= empty($_POST['subject']) ? 'selected' : '' ?>>Select a service…</option>
                <?php foreach(['Account Reinstatement','Fund Hold Resolution','Appeal Writing','IP Complaint Removal','Listing Activation','Legal Documentation','Arbitration','Amazon Advertising','Other'] as $opt): ?>
                  <option <?= (($_POST['subject'] ?? '') === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="field">
            <label>Message <span class="req">*</span></label>
            <textarea name="message" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
          </div>

          <div class="checkbox-field">
            <input type="checkbox" id="terms" name="terms" value="1" <?= isset($_POST['terms']) ? 'checked' : '' ?> required>
            <label for="terms">I agree to the <a href="terms.php" target="_blank">Terms of Service</a>.</label>
          </div>

          <button type="submit" class="submit-btn">Send Message</button>
        </form>
      </div>
    </div>
  </div>
</section>

<footer>
  <p>© 2025 Seller Rescue | <a href="mailto:syedgillani088@gmail.com">syedgillani088@gmail.com</a> | <a href="tel:+923439737494">+92 343 9737494</a></p>
</footer>
</body>
</html>