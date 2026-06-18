<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';
if (!empty($_SESSION['admin'])) { header('Location: index.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (adminLogin($_POST['username'], $_POST['password'])) {
        header('Location: index.php'); exit;
    }
    $err = 'Invalid credentials.';
}
?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Admin Login</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#1a1a2e;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:Arial,sans-serif}
.box{background:#16213e;padding:40px;border-radius:10px;width:340px}
h2{color:#e94560;text-align:center;margin-bottom:24px}
input{width:100%;padding:10px 14px;margin-bottom:14px;background:#0f3460;border:1px solid #e9456033;color:#fff;border-radius:6px;font-size:14px}
button{width:100%;padding:11px;background:#e94560;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:15px}
.err{color:#ff6b6b;font-size:13px;margin-bottom:10px;text-align:center}
</style></head>
<body>
<div class="box">
  <h2>Admin Login</h2>
  <?php if($err) echo "<p class='err'>$err</p>"; ?>
  <form method="POST">
    <input name="username" placeholder="Username" required>
    <input name="password" type="password" placeholder="Password" required>
    <button>Login</button>
  </form>
</div>
</body></html>
