<?php
$host = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'loan_db';

$pdo = null;
$dbConnected = false;   
$dbError = '';

// DEFAULT VALUES (IMPORTANT 🔥)
$siteStats = [
    'users' => 0,
    'loans' => 0,
    'savings_accounts' => 0,
];

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $dbConnected = true;

} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

// ONLY RUN IF CONNECTED
if ($dbConnected && $pdo) {

    try {
        $statsQueries = [
            'users' => "SELECT COUNT(*) FROM users",
            'loans' => "SELECT COUNT(*) FROM loans",
            'savings_accounts' => "SELECT COUNT(*) FROM users",
        ];

        foreach ($statsQueries as $key => $sql) {
            $stmt = $pdo->query($sql);
            $siteStats[$key] = (int) $stmt->fetchColumn();
        }

    } catch (PDOException $e) {
        // Prevent crash if table/column doesn't exist
        $dbError = $e->getMessage();
        $siteStats = [
            'users' => 0,
            'loans' => 0,
            'savings_accounts' => 0,
        ];
    }
}
?>