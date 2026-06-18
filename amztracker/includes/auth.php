<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function adminLogin($username, $password) {
    $data = readData();
    foreach ($data['admins'] as $admin) {
        if ($admin['username'] === $username && password_verify($password, $admin['password'])) {
            $_SESSION['admin'] = $username;
            return true;
        }
    }
    return false;
}

function requireAdmin() {
    if (empty($_SESSION['admin'])) {
        header('Location: login.php');
        exit;
    }
}

function clientLogin($uid) {
    $user = findUserByUID(trim($uid));
    if ($user) {
        $_SESSION['client_id'] = $user['id'];
        $_SESSION['client_name'] = $user['name'];
        return true;
    }
    return false;
}

function requireClient() {
    if (empty($_SESSION['client_id'])) {
        header('Location: login.php');
        exit;
    }
}
