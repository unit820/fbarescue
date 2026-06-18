<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';
requireAdmin();
$data = readData();
$users = $data['users'];
$accounts = $data['accounts'];
?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Admin Dashboard</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;background:#f0f2f5;min-height:100vh}
nav{background:#1a1a2e;padding:14px 28px;display:flex;justify-content:space-between;align-items:center}
nav span{color:#fff;font-size:18px;font-weight:bold}
nav a{color:#e94560;text-decoration:none;font-size:14px}
.wrap{max-width:1100px;margin:30px auto;padding:0 20px}
h2{color:#1a1a2e;margin-bottom:18px}
.cards{display:flex;gap:20px;margin-bottom:30px}
.card{background:#fff;border-radius:10px;padding:22px 30px;flex:1;box-shadow:0 2px 8px #0001}
.card h3{font-size:32px;color:#e94560}
.card p{color:#777;font-size:13px;margin-top:4px}
.actions{display:flex;gap:12px;margin-bottom:28px}
.btn{display:inline-block;padding:9px 20px;border-radius:6px;text-decoration:none;font-size:14px;cursor:pointer;border:none}
.btn-primary{background:#e94560;color:#fff}
.btn-secondary{background:#0f3460;color:#fff}
table{width:100%;background:#fff;border-radius:10px;box-shadow:0 2px 8px #0001;border-collapse:collapse}
th{background:#1a1a2e;color:#fff;padding:12px 16px;text-align:left;font-size:13px}
td{padding:11px 16px;border-bottom:1px solid #f0f2f5;font-size:14px;color:#333}
tr:last-child td{border:none}
.uid{font-family:monospace;background:#f0f2f5;padding:2px 8px;border-radius:4px;font-size:12px}
.a-links a{margin-right:10px;font-size:13px;text-decoration:none;color:#0f3460}
.a-links a:hover{color:#e94560}
</style></head>
<body>
<nav>
  <span>🛠 Admin Panel</span>
  <a href="logout.php">Logout</a>
</nav>
<div class="wrap">
  <div class="cards">
    <div class="card"><h3><?=count($users)?></h3><p>Total Users</p></div>
    <div class="card"><h3><?=count($accounts)?></h3><p>Total Accounts</p></div>
  </div>
  <div class="actions">
    <a class="btn btn-primary" href="add_user.php">+ Add User</a>
    <a class="btn btn-secondary" href="add_account.php">+ Add Account</a>
  </div>
  <h2>Users</h2>
  <table>
    <tr><th>Name</th><th>Email</th><th>Unique ID</th><th>Actions</th></tr>
    <?php if(empty($users)): ?>
    <tr><td colspan="4" style="text-align:center;color:#999">No users yet.</td></tr>
    <?php else: foreach($users as $u): ?>
    <tr>
      <td><?=htmlspecialchars($u['name'])?></td>
      <td><?=htmlspecialchars($u['email'])?></td>
      <td><span class="uid"><?=$u['unique_id']?></span></td>
      <td class="a-links">
        <a href="edit_user.php?id=<?=$u['id']?>">Edit</a>
        <a href="delete_user.php?id=<?=$u['id']?>" onclick="return confirm('Delete user?')">Delete</a>
        <a href="accounts.php?uid=<?=$u['id']?>">Accounts</a>
      </td>
    </tr>
    <?php endforeach; endif; ?>
  </table>
</div>
</body></html>
