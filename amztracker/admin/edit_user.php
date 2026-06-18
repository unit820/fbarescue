<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';
requireAdmin();
$data = readData();
$id = $_GET['id'] ?? '';
$user = null;
$idx  = null;
foreach ($data['users'] as $i => $u) {
    if ($u['id'] === $id) { $user = $u; $idx = $i; break; }
}
if (!$user) { header('Location: index.php'); exit; }
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['users'][$idx]['name']  = trim($_POST['name']);
    $data['users'][$idx]['email'] = trim($_POST['email']);
    writeData($data);
    $msg = "User updated.";
    $user = $data['users'][$idx];
}
?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Edit User</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;background:#f0f2f5;padding:40px}
.box{background:#fff;max-width:480px;margin:auto;padding:36px;border-radius:10px;box-shadow:0 2px 8px #0001}
h2{color:#1a1a2e;margin-bottom:22px}
label{display:block;font-size:13px;color:#555;margin-bottom:5px}
input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;margin-bottom:16px}
.btn{padding:10px 24px;background:#e94560;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px}
.back{color:#0f3460;font-size:13px;text-decoration:none;margin-left:14px}
.msg{background:#d4edda;color:#155724;padding:10px 14px;border-radius:6px;margin-bottom:16px;font-size:14px}
.uid{font-family:monospace;background:#f0f2f5;padding:4px 10px;border-radius:4px;font-size:13px;margin-bottom:18px;display:inline-block}
</style></head>
<body>
<div class="box">
  <h2>Edit User</h2>
  <?php if($msg) echo "<div class='msg'>$msg</div>"; ?>
  <div>Unique ID: <span class="uid"><?=$user['unique_id']?></span></div>
  <form method="POST" style="margin-top:16px">
    <label>Full Name</label>
    <input name="name" value="<?=htmlspecialchars($user['name'])?>" required>
    <label>Email</label>
    <input name="email" type="email" value="<?=htmlspecialchars($user['email'])?>" required>
    <button class="btn">Save</button>
    <a class="back" href="index.php">← Back</a>
  </form>
</div>
</body></html>
