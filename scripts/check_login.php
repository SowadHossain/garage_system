<?php
require_once __DIR__ . '/../config/db.php';

function check_user($username, $password) {
    global $conn;
    $stmt = $conn->prepare('SELECT staff_id, name, role, password_hash, active FROM staff WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    echo "Checking user: $username\n";
    if (!$res) {
        echo "  -> not found\n\n";
        return;
    }
    echo "  -> found: id={$res['staff_id']}, name={$res['name']}, active={$res['active']}\n";
    $ok = password_verify($password, $res['password_hash']);
    echo "  -> password_verify: " . ($ok ? 'true' : 'false') . "\n\n";
}

check_user('admin', 'admin123');
check_user('seed_admin', 'seedpass');

echo "Done.\n";
