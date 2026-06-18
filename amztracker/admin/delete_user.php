<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';
requireAdmin();
$id   = $_GET['id'] ?? '';
$data = readData();
$data['users']    = array_values(array_filter($data['users'],    fn($u) => $u['id'] !== $id));
$data['accounts'] = array_values(array_filter($data['accounts'], fn($a) => $a['user_id'] !== $id));
writeData($data);
header('Location: index.php');
exit;
