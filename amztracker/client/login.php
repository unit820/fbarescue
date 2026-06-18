<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';
if (!empty($_SESSION['client_id'])) { header('Location: dashboard.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (clientLogin($_POST['unique_id'])) { header('Location: dashboard.php'); exit; }
    $err = 'Invalid ID. Please check and try again.';
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Client Portal — Amazon Account Tracker</title>
<link rel="stylesheet" href="../assets/css/client.css">
</head>
<body class="auth-page">

<div class="auth-card">
  <div class="auth-logo">
    <svg width="36" height="36" viewBox="0 0 42 42"><circle cx="21" cy="21" r="21" fill="#FF9900"/><text x="50%" y="57%" dominant-baseline="middle" text-anchor="middle" font-size="24" font-weight="800" fill="#000" font-family="Arial">a</text></svg>
    <div class="auth-logo-text">Account<span>Tracker</span></div>
  </div>

  <h1>Welcome Back</h1>
  <p class="subtitle">Access your Amazon account recovery dashboard with your unique client ID.</p>

  <?php if($err): ?><div class="alert alert-error">⚠️ <?=$err?></div><?php endif; ?>

  <form method="POST" class="auth-form">
    <div class="field">
      <label>Your Client ID</label>
      <input name="unique_id" placeholder="AMZ-XXXXXXXX" autocomplete="off" required autofocus spellcheck="false">
    </div>
    <button type="submit" class="btn-main">Access My Dashboard →</button>
  </form>

  <div class="trust-row">
    <div class="trust-item">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#22c55e" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
      Secure Portal
    </div>
    <div class="trust-item">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#60a5fa" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Live Updates
    </div>
    <div class="trust-item">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#a78bfa" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
      Email Alerts
    </div>
  </div>

  <p class="help-text">Don't have an ID? Contact your account manager.</p>
</div>

<p style="margin-top:28px;font-size:12px;color:var(--muted);text-align:center">
  Powered by <a href="https://unit820.github.io/fbarescue/" target="_blank" style="color:var(--accent);font-weight:600">Seller Rescue</a> · Amazon Specialist Since 2016
</p>

</body>
</html>
