<?php
// ============================================================
//  IP Tracker — Single File PHP App
//  Data saved in: visitors.json (same directory)
// ============================================================

$dataFile = __DIR__ . '/visitors.json';

// ── Helper: get real visitor IP ──────────────────────────────
function getRealIP(): string {
    $headers = ['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'];
    foreach ($headers as $h) {
        if (!empty($_SERVER[$h])) {
            $ip = trim(explode(',', $_SERVER[$h])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    // fallback (local / dev)
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

// ── Helper: generate unique tag like TAG-A3F9 ────────────────
function generateTag(string $ip): string {
    return 'TAG-' . strtoupper(substr(md5($ip . 'salt_2024'), 0, 4));
}

// ── Helper: fetch IP geo-info from ip-api.com (free) ────────
function fetchGeoInfo(string $ip): array {
    // For localhost testing, return demo data
    if (in_array($ip, ['127.0.0.1', '::1'])) {
        return [
            'country'      => 'Localhost',
            'countryCode'  => 'LC',
            'regionName'   => 'Local',
            'city'         => 'Local Machine',
            'isp'          => 'Localhost',
            'org'          => 'Local Network',
            'timezone'     => 'N/A',
            'lat'          => 0,
            'lon'          => 0,
            'status'       => 'success',
        ];
    }

    $url  = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,regionName,city,isp,org,timezone,lat,lon";
    $ctx  = stream_context_create(['http' => ['timeout' => 5]]);
    $resp = @file_get_contents($url, false, $ctx);

    if ($resp) {
        $data = json_decode($resp, true);
        if (isset($data['status']) && $data['status'] === 'success') {
            return $data;
        }
    }
    return ['status' => 'fail', 'country' => 'Unknown', 'countryCode' => '??',
            'regionName' => '—', 'city' => '—', 'isp' => '—', 'org' => '—',
            'timezone' => '—', 'lat' => 0, 'lon' => 0];
}

// ── Load existing data ───────────────────────────────────────
$allVisitors = [];
if (file_exists($dataFile)) {
    $raw = file_get_contents($dataFile);
    $allVisitors = json_decode($raw, true) ?? [];
}

// ── Record current visitor ──────────────────────────────────
$visitorIP  = getRealIP();
$uniqueTag  = generateTag($visitorIP);
$visitTime  = date('Y-m-d H:i:s');
$userAgent  = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Check if this IP already exists, else fetch fresh geo
$existingEntry = null;
foreach ($allVisitors as $v) {
    if ($v['ip'] === $visitorIP) { $existingEntry = $v; break; }
}

if ($existingEntry) {
    $geoInfo = $existingEntry['geo'];
    // update visit count & last seen
    foreach ($allVisitors as &$v) {
        if ($v['ip'] === $visitorIP) {
            $v['visits']++;
            $v['last_seen'] = $visitTime;
        }
    }
    unset($v);
} else {
    $geoInfo = fetchGeoInfo($visitorIP);
    $allVisitors[] = [
        'tag'        => $uniqueTag,
        'ip'         => $visitorIP,
        'first_seen' => $visitTime,
        'last_seen'  => $visitTime,
        'visits'     => 1,
        'user_agent' => $userAgent,
        'geo'        => $geoInfo,
    ];
}

// ── Save back to JSON ────────────────────────────────────────
file_put_contents($dataFile, json_encode($allVisitors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// ── Current visitor full record ──────────────────────────────
$current = null;
foreach ($allVisitors as $v) {
    if ($v['ip'] === $visitorIP) { $current = $v; break; }
}

// Flag emoji helper
function flagEmoji(string $code): string {
    if (strlen($code) !== 2) return '🌐';
    $offset = 127397;
    return mb_chr(ord($code[0]) + $offset) . mb_chr(ord($code[1]) + $offset);
}

// Total unique IPs
$totalUnique = count($allVisitors);
$totalVisits = array_sum(array_column($allVisitors, 'visits'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IP Tracker</title>
<style>
  /* ── Tokens ── */
  :root {
    --bg:        #0b0f1a;
    --surface:   #111827;
    --border:    #1e2d40;
    --accent:    #00d4ff;
    --accent2:   #7c3aed;
    --text:      #e2e8f0;
    --muted:     #64748b;
    --success:   #10b981;
    --warn:      #f59e0b;
    --font-mono: 'Courier New', monospace;
    --font-sans: 'Segoe UI', system-ui, sans-serif;
    --radius:    12px;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: var(--font-sans);
    min-height: 100vh;
    padding: 24px 16px 60px;
  }

  /* ── Grid scanlines overlay ── */
  body::before {
    content: '';
    position: fixed; inset: 0;
    background: repeating-linear-gradient(
      to bottom,
      transparent 0px, transparent 3px,
      rgba(0,212,255,0.015) 3px, rgba(0,212,255,0.015) 4px
    );
    pointer-events: none; z-index: 0;
  }

  .wrap { max-width: 960px; margin: 0 auto; position: relative; z-index: 1; }

  /* ── Header ── */
  header {
    text-align: center;
    padding: 40px 0 32px;
  }
  .logo {
    display: inline-flex; align-items: center; gap: 10px;
    font-family: var(--font-mono);
    font-size: 11px; letter-spacing: 4px; text-transform: uppercase;
    color: var(--accent); margin-bottom: 16px;
  }
  .logo::before, .logo::after {
    content: ''; display: block;
    width: 40px; height: 1px; background: var(--accent); opacity: .5;
  }
  h1 {
    font-size: clamp(28px, 5vw, 48px);
    font-weight: 800; letter-spacing: -1px;
    background: linear-gradient(135deg, #fff 30%, var(--accent));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  .subtitle {
    margin-top: 8px; color: var(--muted); font-size: 14px;
  }

  /* ── Stats bar ── */
  .stats-bar {
    display: flex; gap: 12px; justify-content: center;
    flex-wrap: wrap; margin: 28px 0;
  }
  .stat-chip {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 999px; padding: 6px 18px;
    font-size: 13px; color: var(--muted);
  }
  .stat-chip strong { color: var(--accent); font-family: var(--font-mono); }

  /* ── MY IP Card ── */
  .my-card {
    background: linear-gradient(135deg, #0f1e35 0%, #1a0f35 100%);
    border: 1px solid rgba(0,212,255,.25);
    border-radius: var(--radius);
    padding: 28px;
    margin-bottom: 32px;
    position: relative; overflow: hidden;
  }
  .my-card::after {
    content: '';
    position: absolute; top: -60px; right: -60px;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(0,212,255,.08) 0%, transparent 70%);
    pointer-events: none;
  }
  .card-label {
    font-family: var(--font-mono); font-size: 10px; letter-spacing: 3px;
    text-transform: uppercase; color: var(--accent); opacity: .7;
    margin-bottom: 8px;
  }
  .ip-display {
    font-family: var(--font-mono); font-size: clamp(22px, 4vw, 36px);
    font-weight: 700; color: #fff; letter-spacing: 1px;
    margin-bottom: 6px;
  }
  .tag-badge {
    display: inline-block;
    background: var(--accent2); color: #fff;
    font-family: var(--font-mono); font-size: 12px; letter-spacing: 2px;
    padding: 3px 12px; border-radius: 999px;
    margin-bottom: 20px;
  }
  .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
  }
  .info-item {
    background: rgba(255,255,255,.04);
    border: 1px solid var(--border);
    border-radius: 8px; padding: 12px 14px;
  }
  .info-item .lbl { font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--muted); margin-bottom: 4px; }
  .info-item .val { font-size: 14px; font-weight: 600; word-break: break-word; }

  /* ── Section heading ── */
  .section-head {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 16px;
  }
  .section-head h2 {
    font-size: 16px; font-weight: 700; letter-spacing: -.3px;
  }
  .section-head .count {
    background: var(--border); color: var(--muted);
    font-family: var(--font-mono); font-size: 11px;
    padding: 2px 8px; border-radius: 999px;
  }

  /* ── Visitors table ── */
  .table-wrap { overflow-x: auto; border-radius: var(--radius); border: 1px solid var(--border); }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  thead { background: #0d1521; }
  thead th {
    padding: 12px 14px; text-align: left;
    font-family: var(--font-mono); font-size: 10px; letter-spacing: 2px;
    text-transform: uppercase; color: var(--muted);
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
  }
  tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background .15s;
  }
  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: rgba(0,212,255,.04); }
  tbody tr.current-row { background: rgba(0,212,255,.07); }
  td { padding: 11px 14px; vertical-align: middle; white-space: nowrap; }
  .tag-cell {
    font-family: var(--font-mono); font-size: 11px; letter-spacing: 1px;
    color: var(--accent2); font-weight: 700;
  }
  .ip-cell { font-family: var(--font-mono); font-size: 12px; }
  .you-badge {
    display: inline-block; background: var(--accent);
    color: #000; font-size: 9px; font-weight: 800; letter-spacing: 1px;
    padding: 2px 6px; border-radius: 999px; margin-left: 6px;
    vertical-align: middle;
  }
  .visits-cell {
    font-family: var(--font-mono); color: var(--success); font-weight: 700;
  }
  .flag { font-size: 18px; }

  /* ── Footer ── */
  footer {
    text-align: center; margin-top: 48px;
    font-size: 12px; color: var(--muted); font-family: var(--font-mono);
    letter-spacing: 1px;
  }
  footer a { color: var(--accent); text-decoration: none; }

  /* ── Pulse dot ── */
  .pulse {
    display: inline-block; width: 8px; height: 8px;
    background: var(--success); border-radius: 50%;
    margin-right: 6px; vertical-align: middle;
    animation: pulse 2s infinite;
  }
  @keyframes pulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(16,185,129,.6); }
    50%      { box-shadow: 0 0 0 6px rgba(16,185,129,0); }
  }

  @media (max-width: 600px) {
    .info-grid { grid-template-columns: 1fr 1fr; }
  }
</style>
</head>
<body>
<div class="wrap">

  <!-- Header -->
  <header>
    <div class="logo">IP Tracker</div>
    <h1>Visitor Intelligence</h1>
    <p class="subtitle"><span class="pulse"></span>Live tracking · Data saved to JSON</p>
  </header>

  <!-- Stats -->
  <div class="stats-bar">
    <div class="stat-chip">Unique IPs: <strong><?= $totalUnique ?></strong></div>
    <div class="stat-chip">Total Visits: <strong><?= $totalVisits ?></strong></div>
    <div class="stat-chip">Last Updated: <strong><?= date('H:i:s') ?></strong></div>
    <div class="stat-chip">Data File: <strong>visitors.json</strong></div>
  </div>

  <?php if ($current): ?>
  <!-- My IP Card -->
  <div class="my-card">
    <div class="card-label">🎯 Aapki Information</div>
    <div class="ip-display"><?= htmlspecialchars($current['ip']) ?></div>
    <div class="tag-badge"><?= htmlspecialchars($current['tag']) ?></div>

    <div class="info-grid">
      <div class="info-item">
        <div class="lbl">🌍 Country</div>
        <div class="val">
          <?= flagEmoji($current['geo']['countryCode'] ?? '??') ?>
          <?= htmlspecialchars($current['geo']['country'] ?? 'Unknown') ?>
        </div>
      </div>
      <div class="info-item">
        <div class="lbl">🏙️ City / Region</div>
        <div class="val"><?= htmlspecialchars(($current['geo']['city'] ?? '—') . ', ' . ($current['geo']['regionName'] ?? '—')) ?></div>
      </div>
      <div class="info-item">
        <div class="lbl">🕐 Timezone</div>
        <div class="val"><?= htmlspecialchars($current['geo']['timezone'] ?? '—') ?></div>
      </div>
      <div class="info-item">
        <div class="lbl">🏢 ISP</div>
        <div class="val"><?= htmlspecialchars($current['geo']['isp'] ?? '—') ?></div>
      </div>
      <div class="info-item">
        <div class="lbl">🏛️ Organization</div>
        <div class="val"><?= htmlspecialchars($current['geo']['org'] ?? '—') ?></div>
      </div>
      <div class="info-item">
        <div class="lbl">📍 Coordinates</div>
        <div class="val"><?= $current['geo']['lat'] ?? 0 ?>, <?= $current['geo']['lon'] ?? 0 ?></div>
      </div>
      <div class="info-item">
        <div class="lbl">🕒 Visit Time</div>
        <div class="val"><?= htmlspecialchars($visitTime) ?></div>
      </div>
      <div class="info-item">
        <div class="lbl">🔢 Your Visits</div>
        <div class="val" style="color:var(--success)"><?= $current['visits'] ?></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- All Visitors -->
  <div class="section-head">
    <h2>📋 Saray Visitors</h2>
    <span class="count"><?= $totalUnique ?> IPs</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Tag</th>
          <th>IP Address</th>
          <th>Flag</th>
          <th>Country</th>
          <th>City</th>
          <th>ISP</th>
          <th>Timezone</th>
          <th>Visits</th>
          <th>First Seen</th>
          <th>Last Seen</th>
        </tr>
      </thead>
      <tbody>
        <?php
        // Sort by last_seen desc
        usort($allVisitors, fn($a,$b) => strcmp($b['last_seen'], $a['last_seen']));
        foreach ($allVisitors as $i => $v):
            $isMe = ($v['ip'] === $visitorIP);
            $geo  = $v['geo'] ?? [];
        ?>
        <tr class="<?= $isMe ? 'current-row' : '' ?>">
          <td style="color:var(--muted)"><?= $i+1 ?></td>
          <td class="tag-cell"><?= htmlspecialchars($v['tag']) ?></td>
          <td class="ip-cell">
            <?= htmlspecialchars($v['ip']) ?>
            <?= $isMe ? '<span class="you-badge">YOU</span>' : '' ?>
          </td>
          <td class="flag"><?= flagEmoji($geo['countryCode'] ?? '??') ?></td>
          <td><?= htmlspecialchars($geo['country'] ?? '—') ?></td>
          <td><?= htmlspecialchars(($geo['city'] ?? '—') . ($geo['regionName'] ? ', '.$geo['regionName'] : '')) ?></td>
          <td style="color:var(--muted);max-width:180px;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($geo['isp'] ?? '—') ?></td>
          <td style="color:var(--muted)"><?= htmlspecialchars($geo['timezone'] ?? '—') ?></td>
          <td class="visits-cell"><?= (int)$v['visits'] ?></td>
          <td style="color:var(--muted)"><?= htmlspecialchars($v['first_seen']) ?></td>
          <td style="color:var(--warn)"><?= htmlspecialchars($v['last_seen']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <footer>
    <p style="margin-bottom:6px">Data stored in <code>visitors.json</code> · Geo by ip-api.com</p>
    <p>Refresh to update · <?= $visitTime ?></p>
  </footer>

</div>
</body>
</html>