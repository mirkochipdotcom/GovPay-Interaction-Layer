<?php
//phpinfo();

/*$mysqli = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}*/
echo  getenv('ENTE_TITOLO');

// Carica l'autoloader di Composer, che si trova in /var/www/html/vendor/autoload.php
require __DIR__ . '/vendor/autoload.php';

// Verifichiamo una classe nota del client 'pendenze-client'
// Per convenzione, il generatore OpenAPI crea un'API chiamata [NomeAPI]Api.
$api_class = 'GovPay\Pendenze\Api\PendenzeApi'; 

echo "--- Verifica del Client GovPay/Pendenze ---\n";

if (class_exists($api_class)) {
    echo "✅ SUCCESSO: La classe API è stata trovata.\n";
    echo "Namespace: $api_class\n";
    
    // 1. TENTA DI ISTANZIARE GUZZLE SEPARATAMENTE
    try {
        $guzzle_client = new GuzzleHttp\Client();
        echo "✅ GuzzleHttp\\Client istanziato con successo.\n";

        // 2. TENTA DI ISTANZIARE L'API (se Guzzle ha funzionato)
        $client = new $api_class(
            $guzzle_client,
            new GovPay\Pendenze\Configuration()
        );
        echo "✅ Istanza API creata con successo.\n";

    } catch (\Error $e) {
        // Cattura gli errori di classe non trovata (più comuni)
        echo "❌ ERRORE FATALE (Guzzle?): " . $e->getMessage() . "\n";
        echo "Controlla le dipendenze di Guzzle nel vendor.\n";
    } catch (\Throwable $e) {
        // Cattura altri errori generici o di dipendenza
        echo "⚠️ ATTENZIONE (Istanziazione API fallita): " . $e->getMessage() . "\n";
        echo "L'errore non è un'eccezione, ma un problema di autoloading/ambiente.\n";
    }
}

echo "------------------------------------------------\n";







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
