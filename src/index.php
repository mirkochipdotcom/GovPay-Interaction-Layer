<?php
//phpinfo();

/*$mysqli = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}*/
$dir = __DIR__ . '/public';
$files = scandir($dir);

echo "<h2>CSS files in Bootstrap Italia:</h2><ul>";
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    echo "<li><a href='/assets/bootstrap-italia/css/$file'>$file</a></li>";
}
echo "</ul>";
echo "<p>MariaDB connection successful!</p>";
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GovPay Backend</title>

    <!-- Bootstrap Italia CSS -->
    <link rel="stylesheet" href="/public/assets/bootstrap-italia/css/bootstrap-italia.min.css">
</head>
<body>

<div class="it-container">
    <h1 class="it-title">Benvenuto nel backend GovPay</h1>
    <p>Questa è una pagina di esempio con Bootstrap Italia.</p>
    
    <button class="it-btn it-btn-primary">Cliccami!</button>
</div>

<!-- Bootstrap Italia JS -->
<script src="/public/assets/bootstrap-italia/js/bootstrap-italia.bundle.min.js"></script>

</body>
</html>
