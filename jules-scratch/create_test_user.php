<?php
require_once 'includes/db_connect.php';

$username = 'testuser';
$password = password_hash('password', PASSWORD_DEFAULT);
$email = 'testuser@example.com';

$sql = "INSERT INTO users (username, password, email, is_verified) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE password = VALUES(password)";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("sss", $username, $password, $email);
$stmt->execute();
$stmt->close();

echo "Test user created/updated.";