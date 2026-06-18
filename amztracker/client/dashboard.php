<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';
requireClient();

$all = getUserAccounts($_SESSION['client_id']);
$filter = $_GET['filter'] ?? 'active';

$activeStatuses = ['Pending','Under Review','Action Required','Appeal Submitted',
                   'Appeal Under Review','Additional Info Requested','Escalated',
                   'Partially Reinstated','Active'];
$wonStatuses    = ['Reinstated','Issue Resolved','Case Closed — Won'];

function hasOpenIssues($acc) {
    foreach ($acc['issues'] ?? [] as $iss) {
        if (in_array($iss['status'], ['Open','In Progress','Escalated'])) return true;
    }
    return false;
}

function isWon($acc) {
    $status = $acc['status'] ?? 'Pending';
    global $wonStatuses;
    // Must have won status AND no open issues
    return in_array($status, $wonStatuses) && !hasOpenIssues($acc);
}

function isActive($acc) {
    $status = $acc['status'] ?? 'Pending';
    global $activeStatuses, $wonStatuses;
    return in_array($status, $activeStatuses) || hasOpenIssues($acc);
}

$activeCount = count(array_filter($all, fn($a) => isActive($a)));
$wonCount    = array_sum(array_map(fn($a) =>
    count(array_filter($a['issues'] ?? [], fn($i) => $i['status'] === 'Resolved'))
    + (in_array($a['status'] ?? '', ['Reinstated','Issue Resolved','Case Closed — Won']) && !hasOpenIssues($a) && empty($a['issues']) ? 1 : 0)
, $all));

if ($filter === 'won')
    $accounts = array_values(array_filter($all, fn($a) => isWon($a)));
elseif ($filter === 'all')
    $accounts = $all;
else
    $accounts = array_values(array_filter($all, fn($a) => isActive($a)));

$initials = strtoupper(substr($_SESSION['client_name'], 0, 2));
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Dashboard — Amazon Account Tracker</title>
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

  <div class="dash-head">
    <div>
      <h2>My Accounts</h2>
      <p>Track recovery progress and updates for your Amazon accounts.</p>
    </div>
    <div class="filter-tabs">
      <a href="dashboard.php?filter=active" class="ftab <?=$filter==='active'?'ftab-active':''?>">Active &nbsp;<strong><?=$activeCount?></strong></a>
      <a href="dashboard.php?filter=won"    class="ftab ftab-won <?=$filter==='won'?'ftab-won-active':''?>">🏆 Won &nbsp;<strong><?=$wonCount?></strong></a>
      <a href="dashboard.php?filter=all"    class="ftab <?=$filter==='all'?'ftab-active':''?>">All &nbsp;<strong><?=count($all)?></strong></a>
    </div>
  </div>

  <?php if(empty($accounts)): ?>
  <div class="empty-state">
    <div class="empty-icon"><?= $filter==='won' ? '🏆' : ($filter==='all' ? '📦' : '✅') ?></div>
    <p><?= $filter==='won'
      ? 'No resolved cases yet. Keep going — we\'re working on it!'
      : ($filter==='all'
        ? 'No accounts assigned yet.<br>Please contact your account manager.'
        : 'No active cases right now.<br><a href="dashboard.php?filter=all" style="color:var(--accent)">View all accounts →</a>') ?></p>
  </div>
  <?php elseif($filter === 'won'): ?>
  <div class="won-banner">
    <div class="won-banner-icon">🏆</div>
    <div>
      <div class="won-banner-title">Successfully Resolved Cases</div>
      <div class="won-banner-sub"><?=$wonCount?> issue<?=$wonCount!=1?'s':''?> resolved across all your accounts.</div>
    </div>
  </div>

  <div class="accounts-grid">
  <?php foreach($all as $acc):
    $sc = strtolower(str_replace([' ','/','—',' '],['-','-','-','-'], $acc['status'] ?? ''));
    $resolvedIssues = array_values(array_filter($acc['issues'] ?? [], fn($i) => $i['status'] === 'Resolved'));

    // Account with no issues but won status — show as one card
    if(empty($acc['issues']) && isWon($acc)):
  ?>
    <a href="account.php?id=<?=urlencode($acc['id'])?>" class="acc-card acc-card-won">
      <div class="won-check">✓</div>
      <div class="acc-top" style="margin-bottom:10px">
        <div>
          <div class="acc-name"><?=htmlspecialchars($acc['account_name'])?></div>
          <div class="acc-sub">Account fully resolved</div>
        </div>
        <span class="status-badge status-<?=$sc?>"><?=htmlspecialchars($acc['status'])?></span>
      </div>
      <div class="won-prog-row">
        <div class="progress-track" style="flex:1"><div class="progress-fill" style="width:100%;background:#22c55e"></div></div>
        <span style="font-size:13px;font-weight:800;color:#22c55e">100%</span>
      </div>
      <div class="acc-arrow" style="color:#22c55e">View Details <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></div>
    </a>

  <?php else: foreach($resolvedIssues as $iss): ?>
    <a href="account.php?id=<?=urlencode($acc['id'])?>" class="acc-card acc-card-won won-issue-card">
      <div class="won-check">✓</div>

      <div class="wi-top">
        <div class="wi-icon">✅</div>
        <div class="wi-badge">Issue Resolved</div>
      </div>

      <div class="wi-title"><?=htmlspecialchars($iss['title'])?></div>

      <?php if(!empty($iss['detail'])): ?>
      <div class="wi-detail"><?=nl2br(htmlspecialchars($iss['detail']))?></div>
      <?php endif; ?>

      <div class="wi-footer">
        <span class="wi-account">
          <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18"/></svg>
          <?=htmlspecialchars($acc['account_name'])?>
        </span>
        <span class="wi-date"><?=$iss['date']?></span>
      </div>
    </a>
  <?php endforeach; endif; endforeach; ?>
  </div>
  <?php else: ?>
  <div class="accounts-grid">
    <?php foreach($accounts as $acc):
      $p = (int)($acc['progress'] ?? 0);
      $pColor = $p >= 75 ? '#22c55e' : ($p >= 40 ? '#FF9900' : '#ef4444');
      $sc = strtolower(str_replace([' ','/','—',' '], ['-','-','-','-'], $acc['status'] ?? 'pending'));
      $lastUpdate = !empty($acc['updates']) ? end($acc['updates'])['at'] : null;
      $openIssues = count(array_filter($acc['issues'] ?? [], fn($i) => in_array($i['status'], ['Open','In Progress','Escalated'])));
      $resolvedIssues = count(array_filter($acc['issues'] ?? [], fn($i) => $i['status'] === 'Resolved'));
    ?>
    <a href="account.php?id=<?=urlencode($acc['id'])?>" class="acc-card">
      <div class="acc-top">
        <div>
          <div class="acc-name"><?=htmlspecialchars($acc['account_name'])?></div>
          <?php if($lastUpdate): ?>
          <div class="acc-sub">Updated <?=$lastUpdate?></div>
          <?php endif; ?>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px">
          <span class="status-badge status-<?=$sc?>"><?=htmlspecialchars($acc['status'] ?? 'Pending')?></span>
          <?php if($openIssues > 0): ?>
          <span style="font-size:10px;font-weight:700;background:rgba(249,115,22,.15);color:#fdba74;padding:2px 8px;border-radius:99px"><?=$openIssues?> Pending Issue<?=$openIssues>1?'s':''?></span>
          <?php endif; ?>
          <?php if($resolvedIssues > 0): ?>
          <span style="font-size:10px;font-weight:700;background:rgba(34,197,94,.12);color:#4ade80;padding:2px 8px;border-radius:99px"><?=$resolvedIssues?> Resolved</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="progress-wrap">
        <div class="progress-top">
          <span class="progress-label">Recovery Progress</span>
          <span class="progress-pct" style="color:<?=$pColor?>"><?=$p?>%</span>
        </div>
        <div class="progress-track">
          <div class="progress-fill glow" style="width:<?=$p?>%;background:<?=$pColor?>"></div>
        </div>
      </div>

      <div class="acc-arrow">View Details <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php require_once '../includes/footer.php'; ?>
</main>

</body>
</html>
