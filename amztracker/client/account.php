<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';
requireClient();
$acc = getAccountById($_GET['id'] ?? '');
if (!$acc || $acc['user_id'] !== $_SESSION['client_id']) { header('Location: dashboard.php'); exit; }

$p          = (int)($acc['progress'] ?? 0);
$pColor     = $p >= 75 ? '#22c55e' : ($p >= 40 ? '#FF9900' : '#ef4444');
$sc         = strtolower(str_replace([' ','/','—',' '], ['-','-','-','-'], $acc['status'] ?? 'pending'));
$updates    = array_reverse($acc['updates'] ?? []);
$appeals    = array_reverse($acc['appeals'] ?? []);
$issues     = array_reverse($acc['issues']  ?? []);
$initials   = strtoupper(substr($_SESSION['client_name'], 0, 2));

function chipClass($prog) {
    if ($prog >= 75) return 'green';
    if ($prog >= 40) return 'orange';
    return 'red';
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=htmlspecialchars($acc['account_name'])?> — Amazon Tracker</title>
<link rel="stylesheet" href="../assets/css/client.css">
</head>
<body>

<header class="topbar">
  <div class="topbar-inner">
    <div class="brand">
      <svg width="26" height="26" viewBox="0 0 42 42"><circle cx="21" cy="21" r="21" fill="#FF9900"/><text x="50%" y="57%" dominant-baseline="middle" text-anchor="middle" font-size="24" font-weight="800" fill="#000" font-family="Arial">a</text></svg>
      Account<span class="brand-sub">Tracker</span>
    </div>
    <div class="user-info">
      <div class="user-chip">
        <div class="user-avatar"><?=$initials?></div>
        <?=htmlspecialchars($_SESSION['client_name'])?>
      </div>
      <a href="logout.php" class="logout-link">Sign out</a>
    </div>
  </div>
</header>

<main class="main-wrap">

  <a href="dashboard.php" class="back-link">
    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    Back to My Accounts
  </a>

  <!-- Hero Card -->
  <div class="hero-card">
    <div class="hero-top">
      <div>
        <div class="hero-name"><?=htmlspecialchars($acc['account_name'])?></div>
        <span class="status-badge status-<?=$sc?>"><?=htmlspecialchars($acc['status'] ?? 'Pending')?></span>
        <?php if($acc['status']==='Issue Resolved' && !empty($acc['issue_count'])): ?>
        <div style="margin-top:10px;font-size:14px;font-weight:700;color:#4ade80">✅ <?=(int)$acc['issue_count']?> Issue<?=$acc['issue_count']>1?'s':''?> Successfully Resolved</div>
        <?php endif; ?>
      </div>
      <div style="text-align:right">
        <div class="hero-pct" style="color:<?=$pColor?>"><?=$p?>%</div>
        <div class="hero-pct-label">Recovery Progress</div>
      </div>
    </div>

    <div class="progress-wrap">
      <div class="progress-top">
        <span class="progress-label">Overall Progress</span>
        <span class="progress-pct" style="color:<?=$pColor?>"><?=$p?>%</span>
      </div>
      <div class="progress-track lg">
        <div class="progress-fill glow" style="width:<?=$p?>%;background:<?=$pColor?>"></div>
      </div>
    </div>

    <?php if(!empty($updates)): $latest = $updates[0]; ?>
    <div class="latest-note">
      <div class="latest-note-label">
        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Latest Update from Our Team
      </div>
      <p><?=nl2br(htmlspecialchars($latest['note']))?></p>
      <div class="latest-note-time">Posted <?=$latest['at']?></div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Stats Row -->
  <div class="stats-row">
    <div class="stat-box">
      <div class="stat-num" style="color:var(--accent)"><?=count($updates)?></div>
      <div class="stat-lbl">Updates Posted</div>
    </div>
    <div class="stat-box">
      <div class="stat-num" style="color:var(--purple)"><?=count($appeals)?></div>
      <div class="stat-lbl">Appeals Filed</div>
    </div>
    <div class="stat-box">
      <div class="stat-num" style="color:var(--blue)"><?=count($issues)?></div>
      <div class="stat-lbl">Issues Tracked</div>
    </div>
  </div>

  <!-- Update History -->
  <?php if(!empty($updates)): ?>
  <div class="timeline-wrap">
    <div class="section-hd">Update History</div>
    <div class="timeline">
      <?php foreach($updates as $upd):
        $up = (int)$upd['progress'];
        $cc = chipClass($up);
        $uc = $up>=75?'#22c55e':($up>=40?'#FF9900':'#ef4444');
      ?>
      <div class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-box">
          <div class="tl-note"><?=nl2br(htmlspecialchars($upd['note']))?></div>
          <div class="tl-chips">
            <span class="tl-chip <?=$cc?>" style="color:<?=$uc?>"><?=$up?>%</span>
            <span class="tl-chip"><?=htmlspecialchars($upd['status'])?></span>
          </div>
          <div class="tl-time"><?=$upd['at']?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Issues -->
  <?php if(!empty($issues)): ?>
  <div class="timeline-wrap">
    <div class="section-hd">Issues Tracked</div>
    <div class="timeline">
      <?php foreach($issues as $iss):
        $sc2 = strtolower(str_replace(' ','-',$iss['status']));
        $dotColors = ['open'=>'#f97316','in-progress'=>'#60a5fa','resolved'=>'#22c55e','escalated'=>'#a78bfa'];
        $dc = $dotColors[$sc2] ?? 'var(--accent)';
      ?>
      <div class="tl-item">
        <div class="tl-dot" style="background:<?=$dc?>;box-shadow:0 0 0 2px <?=$dc?>"></div>
        <div class="tl-box">
          <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:6px">
            <div class="tl-appeal-title"><?=htmlspecialchars($iss['title'])?></div>
            <span class="tl-chip" style="background:<?=$dc?>22;color:<?=$dc?>"><?=htmlspecialchars($iss['status'])?></span>
          </div>
          <?php if(!empty($iss['detail'])): ?>
          <div class="tl-note"><?=nl2br(htmlspecialchars($iss['detail']))?></div>
          <?php endif; ?>
          <div class="tl-time">Added <?=$iss['date']?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Appeals -->
  <?php if(!empty($appeals)): ?>
  <div class="timeline-wrap">
    <div class="section-hd">Appeals Filed</div>
    <div class="timeline">
      <?php foreach($appeals as $ap): ?>
      <div class="tl-item">
        <div class="tl-dot purple"></div>
        <div class="tl-box">
          <div class="tl-appeal-title">
            <svg style="vertical-align:middle;margin-right:6px" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="var(--purple)" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <?=htmlspecialchars($ap['title'])?>
          </div>
          <div class="tl-time">Filed <?=$ap['date']?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if(empty($updates) && empty($appeals)): ?>
  <div class="empty-state">
    <div class="empty-icon">⏳</div>
    <p>Our team is working on your case.<br>Updates will appear here shortly.</p>
  </div>
  <?php endif; ?>

  <?php require_once '../includes/footer.php'; ?>

</main>

</body>
</html>
