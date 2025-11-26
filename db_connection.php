<?php 

// Define database credentials
define("DB_USER", 'root');
define("DB_PASSWORD", '');
define("DB_NAME", 'oninz');
define("DB_HOST", 'localhost');

try {
    // 1. Set up the Data Source Name (DSN)
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    // 2. Set options (Best practices for error handling and security)
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return arrays by default
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
    ];

    // 3. Create the PDO instance
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);

} catch (PDOException $e) {
    // Handle connection errors
    die("Connection failed: " . $e->getMessage());
}

?>