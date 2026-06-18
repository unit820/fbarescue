<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/mailer.php';
requireAdmin();
$data = readData();
$uid  = $_GET['uid'] ?? '';
$user = null;
foreach ($data['users'] as $u) {
    if ($u['id'] === $uid) { $user = $u; break; }
}
if (!$user) { header('Location: index.php'); exit; }

// Delete account
if (isset($_GET['delete_acc'])) {
    $data['accounts'] = array_values(array_filter($data['accounts'], fn($a) => $a['id'] !== $_GET['delete_acc']));
    writeData($data);
    header("Location: accounts.php?uid=$uid"); exit;
}

// Rename account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_acc_id'])) {
    foreach ($data['accounts'] as &$acc) {
        if ($acc['id'] === $_POST['rename_acc_id'] && $acc['user_id'] === $uid) {
            $acc['account_name'] = trim($_POST['new_name']);
            break;
        }
    }
    unset($acc);
    writeData($data);
    header("Location: accounts.php?uid=$uid"); exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acc_id'])) {
    foreach ($data['accounts'] as &$acc) {
        if ($acc['id'] !== $_POST['acc_id'] || $acc['user_id'] !== $uid) continue;
        $updateNote = trim($_POST['update_note'] ?? '');
        if ($updateNote !== '') {
            if (!isset($acc['updates'])) $acc['updates'] = [];
            $acc['updates'][] = [
                'note'     => $updateNote,
                'status'   => $acc['status'],
                'progress' => $acc['progress'],
                'by'       => $_SESSION['admin'],
                'at'       => date('d M Y, h:i A')
            ];
        }
        if (!empty(trim($_POST['appeal_title'] ?? ''))) {
            if (!isset($acc['appeals'])) $acc['appeals'] = [];
            $acc['appeals'][] = ['title' => trim($_POST['appeal_title']), 'date' => date('d M Y')];
        }
        writeData($data);
        sendUpdateEmail($user['email'], $user['name'], $acc['account_name'], $acc['status'], $acc['progress'], $updateNote);
        $msg = "✅ Note saved & email sent to {$user['email']}";
        break;
    }
    unset($acc);
    $data = readData();
}
$accounts = array_values(array_filter($data['accounts'], fn($a) => $a['user_id'] === $uid));
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Accounts — <?=htmlspecialchars($user['name'])?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6fb;min-height:100vh}
nav{background:#1a1a2e;padding:14px 28px;display:flex;justify-content:space-between;align-items:center}
nav .brand{color:#fff;font-weight:700;font-size:16px}
nav a{color:#e94560;font-size:13px;text-decoration:none}
.wrap{max-width:860px;margin:32px auto;padding:0 20px}
.user-bar{background:#fff;border-radius:10px;padding:18px 22px;display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;box-shadow:0 1px 6px #0001;border-left:4px solid #e94560}
.user-bar h2{font-size:17px;color:#1a1a2e}.user-bar p{font-size:13px;color:#888;margin-top:3px}
.uid-badge{font-family:monospace;background:#f0f2f5;padding:5px 12px;border-radius:6px;font-size:13px;color:#1a1a2e;font-weight:600}
.toast{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:14px;background:#d1fae5;color:#065f46;border:1px solid #a7f3d0}
.acc-card{background:#fff;border-radius:12px;margin-bottom:28px;box-shadow:0 2px 10px #0001;overflow:hidden}
.acc-card-head{padding:16px 24px;background:#1a1a2e;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.acc-card-head h3{color:#fff;font-size:15px}
.head-controls{display:flex;align-items:center;gap:10px}
.status-select{padding:5px 10px;border-radius:6px;border:none;font-size:13px;font-weight:600;background:#2d2f45;color:#fff;cursor:pointer;min-width:180px}
.status-select:focus{outline:none}
.saved-chip{font-size:11px;color:#4ade80;display:none;margin-left:4px}
.acc-body{padding:24px}
.progress-row{display:flex;align-items:center;gap:14px;margin-bottom:20px}
.progress-row label{font-size:12px;color:#666;white-space:nowrap;font-weight:600;min-width:80px}
.progress-row input[type=range]{flex:1;accent-color:#e94560}
.progress-num{font-size:15px;font-weight:700;color:#1a1a2e;min-width:38px;text-align:right}
.progress-bar-preview{height:6px;background:#f0f2f5;border-radius:99px;overflow:hidden;flex:1}
.progress-bar-fill{height:100%;border-radius:99px;transition:width .3s;background:#e94560}
.section-title{font-size:11px;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px;margin-top:18px}
.section-title:first-child{margin-top:0}
label.field-label{display:block;font-size:12px;color:#666;margin-bottom:5px;font-weight:500}
input[type=text],textarea{width:100%;padding:9px 12px;border:1px solid #e0e0e0;border-radius:8px;font-size:14px;font-family:inherit;background:#fafafa;transition:border-color .2s}
input[type=text]:focus,textarea:focus{outline:none;border-color:#e94560;background:#fff}
textarea{resize:vertical;min-height:90px}
.btn-update{margin-top:16px;padding:11px 28px;background:#e94560;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer}
.btn-update:hover{background:#c73652}
.divider{border:none;border-top:1px solid #f0f2f5;margin:20px 0}
.log-list{list-style:none}
.log-item{display:flex;gap:12px;padding:11px 0;border-bottom:1px solid #f4f6fb}
.log-item:last-child{border:none;padding-bottom:0}
.log-dot{width:9px;height:9px;border-radius:50%;background:#e94560;flex-shrink:0;margin-top:6px}
.log-note{font-size:14px;color:#1a1a2e;line-height:1.5}
.log-meta{font-size:12px;color:#bbb;margin-top:3px}
.log-meta b{color:#e94560}
.log-empty,.appeal-empty{font-size:13px;color:#ccc;padding:8px 0}
.appeal-list{list-style:none}
.appeal-item{display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid #f4f6fb;font-size:14px;color:#333}
.appeal-item:last-child{border:none}
.appeal-date{margin-left:auto;font-size:12px;color:#aaa}
/* Issue Resolve Modal */
.ir-overlay{display:none;position:fixed;inset:0;background:#0008;z-index:999;align-items:center;justify-content:center}
.ir-overlay.open{display:flex}
.ir-box{background:#fff;border-radius:14px;padding:30px;width:100%;max-width:400px;box-shadow:0 12px 40px #0004}
.ir-box h4{font-size:16px;color:#1a1a2e;margin-bottom:6px}
.ir-box p{font-size:13px;color:#888;margin-bottom:20px}
.ir-options{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
.ir-opt{padding:14px 10px;border:2px solid #e0e0e0;border-radius:10px;text-align:center;cursor:pointer;transition:all .2s;background:#fafafa}
.ir-opt:hover,.ir-opt.selected{border-color:#e94560;background:#fff5f7}
.ir-opt .ir-num{font-size:22px;font-weight:800;color:#e94560}
.ir-opt .ir-lbl{font-size:12px;color:#888;margin-top:2px}
.ir-custom{display:flex;gap:8px;align-items:center;margin-bottom:20px}
.ir-custom input{flex:1;padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px}
.ir-custom input:focus{outline:none;border-color:#e94560}
.ir-actions{display:flex;gap:10px;justify-content:flex-end}
.ir-actions button{padding:9px 20px;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer}
.ir-cancel{background:#f0f2f5;color:#555}
.ir-confirm{background:#e94560;color:#fff}
.add-acc-btn{display:inline-block;margin-top:12px;padding:9px 20px;background:#1a1a2e;color:#fff;border-radius:8px;font-size:14px;text-decoration:none}
.btn-add-issue{padding:4px 12px;background:#1a1a2e;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer}
.issue-item{background:#f9fafb;border:1px solid #eee;border-radius:8px;padding:12px 14px;margin-bottom:8px}
.issue-top{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:4px}
.issue-title{font-size:14px;font-weight:600;color:#1a1a2e}
.issue-detail{font-size:13px;color:#666;line-height:1.6;margin-top:4px}
.issue-date{font-size:11px;color:#bbb}
.issue-del{background:none;border:none;color:#ccc;cursor:pointer;font-size:13px;padding:0 2px;transition:color .2s}
.issue-del:hover{color:#e94560}
.issue-status-chip{font-size:11px;font-weight:700;padding:2px 9px;border-radius:99px;text-transform:uppercase;letter-spacing:.3px}
.issue-open{background:#fef3c7;color:#92400e}
.issue-in-progress{background:#dbeafe;color:#1e40af}
.issue-resolved{background:#d1fae5;color:#065f46}
.issue-escalated{background:#ede9fe;color:#5b21b6}
.head-btn{background:none;border:none;cursor:pointer;font-size:16px;padding:4px 6px;border-radius:6px;text-decoration:none;transition:background .15s}
.head-btn:hover{background:rgba(255,255,255,.12)}
/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:#0007;z-index:999;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:#fff;border-radius:12px;padding:28px;width:100%;max-width:420px;box-shadow:0 8px 32px #0003}
.modal h4{color:#1a1a2e;margin-bottom:16px;font-size:16px}
.modal input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;margin-bottom:16px}
.modal input:focus{outline:none;border-color:#e94560}
.modal-actions{display:flex;gap:10px;justify-content:flex-end}
.modal-actions button{padding:9px 20px;border:none;border-radius:8px;font-size:14px;cursor:pointer;font-weight:600}
.btn-cancel{background:#f0f2f5;color:#555}
.btn-save{background:#e94560;color:#fff}
</style>
</head>
<body>
<nav>
  <span class="brand">🛠 Admin Panel</span>
  <a href="index.php">← Dashboard</a>
</nav>
<div class="wrap">
  <div class="user-bar">
    <div>
      <h2>📋 <?=htmlspecialchars($user['name'])?></h2>
      <p><?=htmlspecialchars($user['email'])?></p>
    </div>
    <span class="uid-badge"><?=$user['unique_id']?></span>
  </div>

  <?php if($msg) echo "<div class='toast'>$msg</div>"; ?>

  <?php if(empty($accounts)): ?>
  <div class="no-acc">
    <p>No accounts assigned yet.</p>
    <a class="add-acc-btn" href="add_account.php">+ Add Account</a>
  </div>
  <?php else: foreach($accounts as $acc):
    $prog    = (int)($acc['progress'] ?? 0);
    $updates = array_reverse($acc['updates'] ?? []);
    $appeals = array_reverse($acc['appeals'] ?? []);
    $issueCount = (int)($acc['issue_count'] ?? 1);
  ?>
  <div class="acc-card" id="card-<?=$acc['id']?>">
    <div class="acc-card-head">
      <h3>🛒 <span class="acc-title"><?=htmlspecialchars($acc['account_name'])?></span>
        <?php if($acc['status']==='Issue Resolved'): ?>
        <span style="font-size:12px;font-weight:600;background:#22c55e22;color:#4ade80;padding:2px 8px;border-radius:99px;margin-left:6px" id="ir-badge-<?=$acc['id']?>"><?=$issueCount?> Issue<?=$issueCount>1?'s':''?> Resolved</span>
        <?php else: ?>
        <span style="display:none" id="ir-badge-<?=$acc['id']?>"></span>
        <?php endif; ?>
      </h3>
      <div class="head-controls">
        <select class="status-select" id="sel-<?=$acc['id']?>" onchange="onStatusChange('<?=$acc['id']?>',this)">
          <?php foreach(['Pending','Under Review','Action Required','Appeal Submitted','Appeal Under Review','Additional Info Requested','Escalated','Reinstated','Partially Reinstated','Active','Issue Resolved','Suspended','Deactivated','Closed','Client Left','Rejected','Case Closed — Won','Case Closed — Lost'] as $s): ?>
          <option <?=($acc['status']??'')===$s?'selected':''?>><?=$s?></option>
          <?php endforeach; ?>
        </select>
        <span class="saved-chip" id="saved-<?=$acc['id']?>">✓ Saved</span>
        <button type="button" class="head-btn edit-btn" title="Rename" onclick="openRename('<?=$acc['id']?>','<?=htmlspecialchars(addslashes($acc['account_name']))?>')">✏️</button>
        <a href="accounts.php?uid=<?=$uid?>&delete_acc=<?=$acc['id']?>" class="head-btn del-btn" title="Delete" onclick="return confirm('Delete this account and all its data?')">🗑️</a>
      </div>
    </div>
    <div class="acc-body">

      <!-- Progress — instant save on release -->
      <div class="progress-row">
        <label>Progress</label>
        <input type="range" min="0" max="100" value="<?=$prog?>"
          oninput="document.getElementById('pnum-<?=$acc['id']?>').textContent=this.value+'%';
                   document.getElementById('pbar-<?=$acc['id']?>').style.width=this.value+'%'"
          onchange="autoSave('<?=$acc['id']?>','progress',this.value,this)">
        <span id="pnum-<?=$acc['id']?>" class="progress-num"><?=$prog?>%</span>
      </div>
      <div class="progress-bar-preview">
        <div class="progress-bar-fill" id="pbar-<?=$acc['id']?>" style="width:<?=$prog?>%"></div>
      </div>

      <!-- Note + Appeal form — only this needs Save button -->
      <form method="POST" style="margin-top:20px">
        <input type="hidden" name="acc_id" value="<?=$acc['id']?>">
        <div class="section-title">Add Update Note</div>
        <label class="field-label">Note for client <span style="color:#e94560">*</span> <span style="color:#bbb;font-weight:normal">(shown on portal & emailed)</span></label>
        <textarea name="update_note" placeholder="e.g. Your appeal has been submitted to Amazon. Expected response in 3-5 business days." required></textarea>
        <div style="margin-top:14px">
          <label class="field-label">Add Appeal <span style="color:#bbb;font-weight:normal">(optional)</span></label>
          <input type="text" name="appeal_title" placeholder="e.g. Plan of Action submitted">
        </div>
        <button type="submit" class="btn-update">📨 Save Note & Notify Client</button>
      </form>

      <hr class="divider">

      <div class="section-title">Update History (<?=count($updates)?>)</div>
      <?php if(empty($updates)): ?>
        <p class="log-empty">No notes added yet.</p>
      <?php else: ?>
        <ul class="log-list">
          <?php foreach($updates as $upd): ?>
          <li class="log-item">
            <div class="log-dot"></div>
            <div>
              <div class="log-note"><?=nl2br(htmlspecialchars($upd['note']))?></div>
              <div class="log-meta"><b><?=htmlspecialchars($upd['status'])?></b> &bull; <?=(int)$upd['progress']?>% &bull; <?=htmlspecialchars($upd['at'])?></div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <?php if(!empty($appeals)): ?>
      <hr class="divider">
      <div class="section-title">Appeals (<?=count($appeals)?>)</div>
      <ul class="appeal-list">
        <?php foreach($appeals as $ap): ?>
        <li class="appeal-item">📄 <?=htmlspecialchars($ap['title'])?><span class="appeal-date"><?=$ap['date']?></span></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>

      <hr class="divider">
      <!-- Issues Section -->
      <div class="section-title" style="display:flex;justify-content:space-between;align-items:center">
        <span>Issues (<?=count($acc['issues']??[])?>)</span>
        <button type="button" class="btn-add-issue" onclick="toggleIssueForm('<?=$acc['id']?>')">+ Add Issue</button>
      </div>

      <!-- Add Issue Form (hidden by default) -->
      <div id="issue-form-<?=$acc['id']?>" style="display:none;background:#f9fafb;border:1px solid #e8e8e8;border-radius:8px;padding:16px;margin-bottom:14px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
          <div>
            <label class="field-label">Issue Title</label>
            <input type="text" id="ititle-<?=$acc['id']?>" placeholder="e.g. Fund Hold">
          </div>
          <div>
            <label class="field-label">Status</label>
            <select id="istatus-<?=$acc['id']?>">
              <option>Open</option>
              <option>In Progress</option>
              <option>Resolved</option>
              <option>Escalated</option>
            </select>
          </div>
        </div>
        <div style="margin-bottom:10px">
          <label class="field-label">Details</label>
          <textarea id="idetail-<?=$acc['id']?>" style="min-height:70px" placeholder="Describe the issue in detail..."></textarea>
        </div>
        <button type="button" style="padding:8px 18px;background:#1a1a2e;color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer" onclick="submitIssue('<?=$acc['id']?>')">Save Issue</button>
        <button type="button" style="margin-left:8px;padding:8px 14px;background:#f0f2f5;color:#555;border:none;border-radius:7px;font-size:13px;cursor:pointer" onclick="toggleIssueForm('<?=$acc['id']?>')">Cancel</button>
      </div>

      <!-- Issues List -->
      <div id="issues-list-<?=$acc['id']?>">
        <?php foreach(array_reverse($acc['issues'] ?? []) as $iss): ?>
        <div class="issue-item" id="iss-<?=$iss['id']?>">
          <div class="issue-top">
            <span class="issue-title"><?=htmlspecialchars($iss['title'])?></span>
            <div style="display:flex;gap:8px;align-items:center">
              <span class="issue-status-chip issue-<?=strtolower(str_replace(' ','-',$iss['status']))?>"><?=$iss['status']?></span>
              <span class="issue-date"><?=$iss['date']?></span>
              <button type="button" class="issue-del" onclick="deleteIssue('<?=$acc['id']?>','<?=$iss['id']?>')" title="Delete">✕</button>
            </div>
          </div>
          <?php if(!empty($iss['detail'])): ?>
          <div class="issue-detail"><?=nl2br(htmlspecialchars($iss['detail']))?></div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if(empty($acc['issues'])): ?>
        <p class="log-empty" id="no-issues-<?=$acc['id']?>">No issues added yet.</p>
        <?php endif; ?>
      </div>

    </div>
  </div>
  <?php endforeach; endif; ?>
</div>

<!-- Issue Resolve Modal -->
<div class="ir-overlay" id="irModal">
  <div class="ir-box">
    <h4>✅ How many issues resolved?</h4>
    <p>Select a preset or enter a custom number.</p>
    <div class="ir-options">
      <div class="ir-opt" onclick="selectIR(1)"><div class="ir-num">1</div><div class="ir-lbl">Issue Resolved</div></div>
      <div class="ir-opt" onclick="selectIR(2)"><div class="ir-num">2</div><div class="ir-lbl">Issues Resolved</div></div>
      <div class="ir-opt" onclick="selectIR(3)"><div class="ir-num">3</div><div class="ir-lbl">Issues Resolved</div></div>
      <div class="ir-opt" onclick="selectIR(0)"><div class="ir-num">#</div><div class="ir-lbl">Custom Count</div></div>
    </div>
    <div class="ir-custom" id="irCustomWrap" style="display:none">
      <input type="number" id="irCustomInput" min="1" placeholder="Enter number of issues" oninput="irSelected=+this.value">
    </div>
    <div class="ir-actions">
      <button class="ir-cancel" onclick="closeIR()">Cancel</button>
      <button class="ir-confirm" onclick="confirmIR()">Confirm</button>
    </div>
  </div>
</div>

<!-- Rename Modal -->
<div class="modal-overlay" id="renameModal">
  <div class="modal">
    <h4>✏️ Rename Account</h4>
    <form method="POST">
      <input type="hidden" name="rename_acc_id" id="renameAccId">
      <input type="text" name="new_name" id="renameInput" required placeholder="Account name">
      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closeRename()">Cancel</button>
        <button type="submit" class="btn-save">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
let irAccId = null, irSelected = 1;

function onStatusChange(accId, sel) {
  if (sel.value === 'Issue Resolved') {
    irAccId = accId;
    irSelected = 1;
    document.querySelectorAll('.ir-opt').forEach(o => o.classList.remove('selected'));
    document.getElementById('irCustomWrap').style.display = 'none';
    document.getElementById('irModal').classList.add('open');
  } else {
    autoSave(accId, 'status', sel.value, sel);
    document.getElementById('ir-badge-' + accId).style.display = 'none';
  }
}

function selectIR(n) {
  document.querySelectorAll('.ir-opt').forEach(o => o.classList.remove('selected'));
  event.currentTarget.classList.add('selected');
  if (n === 0) {
    document.getElementById('irCustomWrap').style.display = 'flex';
    irSelected = +document.getElementById('irCustomInput').value || 1;
  } else {
    irSelected = n;
    document.getElementById('irCustomWrap').style.display = 'none';
  }
}

function confirmIR() {
  if (!irAccId) return;
  const count = irSelected < 1 ? 1 : irSelected;
  // Save status
  autoSave(irAccId, 'status', 'Issue Resolved', null);
  // Save count
  fetch('ajax_update.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'acc_id='+encodeURIComponent(irAccId)+'&field=issue_count&value='+count
  }).then(r=>r.json()).then(d => {
    if (d.ok) {
      const badge = document.getElementById('ir-badge-'+irAccId);
      badge.textContent = count + ' Issue' + (count>1?'s':'') + ' Resolved';
      badge.style.display = 'inline';
    }
  });
  closeIR();
}

function closeIR() {
  // Revert select if cancelled
  document.getElementById('irModal').classList.remove('open');
}

function autoSave(accId, field, value, el) {
  const chip = document.getElementById('saved-' + accId);
  fetch('ajax_update.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'acc_id='+encodeURIComponent(accId)+'&field='+field+'&value='+encodeURIComponent(value)
  }).then(r=>r.json()).then(d => {
    if (d.ok && chip) { chip.style.display='inline'; setTimeout(()=>chip.style.display='none',2000); }
  });
}

function openRename(id, name) {
  document.getElementById('renameAccId').value = id;
  document.getElementById('renameInput').value  = name;
  document.getElementById('renameModal').classList.add('open');
}
function closeRename() { document.getElementById('renameModal').classList.remove('open'); }

function toggleIssueForm(accId) {
  const f = document.getElementById('issue-form-' + accId);
  f.style.display = f.style.display === 'none' ? 'block' : 'none';
}

function submitIssue(accId) {
  const title   = document.getElementById('ititle-'  + accId).value.trim();
  const detail  = document.getElementById('idetail-' + accId).value.trim();
  const istatus = document.getElementById('istatus-' + accId).value;
  if (!title) { alert('Issue title is required.'); return; }

  fetch('ajax_update.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'acc_id='+encodeURIComponent(accId)+'&field=add_issue'
        + '&title='+encodeURIComponent(title)
        + '&detail='+encodeURIComponent(detail)
        + '&istatus='+encodeURIComponent(istatus)
  }).then(r=>r.json()).then(d => {
    if (!d.ok) return;
    const list = document.getElementById('issues-list-' + accId);
    const noMsg = document.getElementById('no-issues-' + accId);
    if (noMsg) noMsg.remove();
    const iss = d.issues[d.issues.length - 1];
    const chipClass = 'issue-' + iss.status.toLowerCase().replace(/ /g,'-');
    const html = `<div class="issue-item" id="iss-${iss.id}">
      <div class="issue-top">
        <span class="issue-title">${esc(iss.title)}</span>
        <div style="display:flex;gap:8px;align-items:center">
          <span class="issue-status-chip ${chipClass}">${esc(iss.status)}</span>
          <span class="issue-date">${esc(iss.date)}</span>
          <button type="button" class="issue-del" onclick="deleteIssue('${accId}','${iss.id}')" title="Delete">✕</button>
        </div>
      </div>
      ${iss.detail ? `<div class="issue-detail">${esc(iss.detail).replace(/\n/g,'<br>')}</div>` : ''}
    </div>`;
    list.insertAdjacentHTML('afterbegin', html);
    // clear form
    document.getElementById('ititle-'+accId).value='';
    document.getElementById('idetail-'+accId).value='';
    toggleIssueForm(accId);
  });
}

function deleteIssue(accId, issId) {
  if (!confirm('Delete this issue?')) return;
  fetch('ajax_update.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'acc_id='+encodeURIComponent(accId)+'&field=del_issue&value='+encodeURIComponent(issId)
  }).then(r=>r.json()).then(d => {
    if (d.ok) document.getElementById('iss-'+issId)?.remove();
  });
}

function esc(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
</body>
</html>
