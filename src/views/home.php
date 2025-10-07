<?php
// Home view extracted from legacy src/index.php
// Use absolute includes relative to this file (located in src/views)
include __DIR__ . '/../templates/header.php';

// Diagnostics: verify GovPay client availability (kept from legacy)
$api_class = 'GovPay\Pendenze\Api\PendenzeApi';
echo "<h3>--- Verifica del Client GovPay/Pendenze ---</h3>\n";
if (class_exists($api_class)) {
    echo "<p>✅ SUCCESSO: La classe API è stata trovata.<br/>Namespace: $api_class</p>\n";
    try {
        $guzzle_client = new GuzzleHttp\Client();
        echo "<p>✅ GuzzleHttp\\Client istanziato con successo.</p>\n";
        $client = new $api_class(
            $guzzle_client,
            new GovPay\Pendenze\Configuration()
        );
        echo "<p>✅ Istanza API creata con successo.</p>\n";
    } catch (\Throwable $e) {
        echo "<p style=\"color:orange\">⚠️ Errore durante l'istanziazione: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
}
echo "<hr/>\n";

// List CSS files in compiled Bootstrap Italia assets (legacy behavior)
$dir = __DIR__ . '/../../public/assets/bootstrap-italia/css';
echo "<h2>CSS files in Bootstrap Italia:</h2><ul>";
if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
    echo '<li><a href="/public/assets/bootstrap-italia/css/' . urlencode($file) . '">' . htmlspecialchars($file) . '</a></li>';
    }
}
echo "</ul>";

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GovPay Backend</title>
    <link rel="stylesheet" href="/public/assets/bootstrap-italia/css/bootstrap-italia.min.css">
</head>
<body>

<div class="it-container">
    <h1 class="it-title">Benvenuto nel backend GovPay</h1>
    <p>Questa è una pagina di esempio con Bootstrap Italia.</p>
    <button class="it-btn it-btn-primary">Cliccami!</button>
</div>

<script src="/public/assets/bootstrap-italia/js/bootstrap-italia.bundle.min.js"></script>

</body>
</html>
