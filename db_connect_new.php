<?php
// START SESSION SAFELY
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* DATABASE CONFIG */
$host = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'loan_db';

/* VARIABLES */
$pdo = null;
$dbConnected = false;
$dbError = '';

$siteStats = [
    'users' => 0,
    'loans' => 0,
    'savings_accounts' => 0,
];

/* CONNECT */
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $dbConnected = true;

} catch (PDOException $e) {
    $dbError = "Database connection failed.";
}

/* FETCH STATS (OPTIONAL) */
if ($dbConnected) {
    try {
        $queries = [
            'users' => "SELECT COUNT(*) FROM users",
            'loans' => "SELECT COUNT(*) FROM loans",
            'savings_accounts' => "SELECT COUNT(*) FROM users"
        ];

        foreach ($queries as $key => $sql) {
            $stmt = $pdo->query($sql);
            $siteStats[$key] = (int) $stmt->fetchColumn();
        }

    } catch (PDOException $e) {
        $dbError = "Error fetching stats.";
    }
}
?>