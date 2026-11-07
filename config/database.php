<?php
// =====================================================
// Connect PHP to MySQL.
// After this file runs, the variable $pdo is ready to use.
// =====================================================

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            // Use the same text collation as MySQL 8, so text comparisons never clash
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_0900_ai_ci'
        ]
    );

    // Show real errors while we are developing
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Rows come back as arrays like $row['title']
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Use real prepared statements (safer)
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    error_log('Lendwise database connection failed: ' . $e->getMessage());
    if (APP_DEBUG) {
        die('Could not connect to the database. Check config/config.php. Details: ' . $e->getMessage());
    }
    die('The system is temporarily unavailable. Please try again later.');
}