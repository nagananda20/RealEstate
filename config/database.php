<?php
/**
 * RealEstateHub database connection.
 * XAMPP defaults: host localhost, user root, empty password.
 */
$host = "localhost";
$dbname = "realestatehub";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die("Database connection failed: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8"));
}

/* Some existing pages use mysqli. Keep one shared connection during migration. */
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_errno) {
    http_response_code(500);
    die("Database connection failed: " . htmlspecialchars($conn->connect_error, ENT_QUOTES, "UTF-8"));
}
$conn->set_charset("utf8mb4");
?>
