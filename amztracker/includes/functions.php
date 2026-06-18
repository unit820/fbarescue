<?php
define('DATA_FILE', __DIR__ . '/../data/data.json');

function readData() {
    return json_decode(file_get_contents(DATA_FILE), true);
}

function writeData($data) {
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

function generateUID() {
    return 'AMZ-' . strtoupper(substr(md5(uniqid()), 0, 8));
}

function findUserByUID($uid) {
    $data = readData();
    foreach ($data['users'] as $u) {
        if ($u['unique_id'] === $uid) return $u;
    }
    return null;
}

function getUserAccounts($user_id) {
    $data = readData();
    return array_values(array_filter($data['accounts'], fn($a) => $a['user_id'] === $user_id));
}

function getAccountById($acc_id) {
    $data = readData();
    foreach ($data['accounts'] as $a) {
        if ($a['id'] === $acc_id) return $a;
    }
    return null;
}
