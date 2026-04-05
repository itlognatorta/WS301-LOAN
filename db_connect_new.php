<?php
$host = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'loan_db';

$pdo = null;
$dbConnected = false;
$dbError = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $dbConnected = true;
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

$siteStats = [
    'users' => 0,
    'loans' => 0,
    'savings_accounts' => 0,
];

if ($dbConnected) {
    $statsQueries = [
        'users' => "SELECT COUNT(*) AS total FROM users WHERE status = 'active'",
        'loans' => "SELECT COUNT(*) AS total FROM loans WHERE status = 'active'",
        'savings_accounts' => "SELECT COUNT(*) AS total FROM users WHERE account_type = 'premium' AND savings_balance > 0",
    ];

    foreach ($statsQueries as $key => $sql) {
        $stmt = $pdo->query($sql);
        $siteStats[$key] = (int) $stmt->fetchColumn();
    }
}
?>

