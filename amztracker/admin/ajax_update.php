<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

$acc_id = $_POST['acc_id'] ?? '';
$field  = $_POST['field']  ?? '';
$value  = $_POST['value']  ?? '';
$data   = readData();

// Add issue
if ($field === 'add_issue') {
    foreach ($data['accounts'] as &$acc) {
        if ($acc['id'] !== $acc_id) continue;
        if (!isset($acc['issues'])) $acc['issues'] = [];
        $acc['issues'][] = [
            'id'     => uniqid(),
            'title'  => trim($_POST['title']   ?? ''),
            'detail' => trim($_POST['detail']  ?? ''),
            'status' => trim($_POST['istatus'] ?? 'Open'),
            'date'   => date('d M Y')
        ];
        writeData($data);
        echo json_encode(['ok'=>true, 'issues'=>$acc['issues']]);
        exit;
    }
    echo json_encode(['ok'=>false]); exit;
}

// Delete issue
if ($field === 'del_issue') {
    foreach ($data['accounts'] as &$acc) {
        if ($acc['id'] !== $acc_id) continue;
        $acc['issues'] = array_values(array_filter($acc['issues'] ?? [], fn($i) => $i['id'] !== $value));
        writeData($data);
        echo json_encode(['ok'=>true]);
        exit;
    }
    echo json_encode(['ok'=>false]); exit;
}

if (!in_array($field, ['status','progress','issue_count'])) { echo json_encode(['ok'=>false]); exit; }

foreach ($data['accounts'] as &$acc) {
    if ($acc['id'] !== $acc_id) continue;
    if ($field === 'progress')        $acc['progress']    = max(0, min(100, (int)$value));
    elseif ($field === 'issue_count') $acc['issue_count'] = max(1, (int)$value);
    else                              $acc['status']      = $value;
    writeData($data);
    echo json_encode(['ok'=>true, 'progress'=>(int)$acc['progress'], 'status'=>$acc['status'], 'issue_count'=>(int)($acc['issue_count']??1)]);
    exit;
}
echo json_encode(['ok'=>false]);
