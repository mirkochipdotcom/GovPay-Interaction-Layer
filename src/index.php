<?php
phpinfo();

$mysqli = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}
echo "<p>MariaDB connection successful!</p>";
