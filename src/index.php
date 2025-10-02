<?php
//phpinfo();

/*$mysqli = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}*/


// Carica l'autoloader di Composer, che si trova in /var/www/html/vendor/autoload.php
require __DIR__ . '/vendor/autoload.php';

// Verifichiamo una classe nota del client 'pagamenti-client'
// Per convenzione, il generatore OpenAPI crea un'API chiamata [NomeAPI]Api.
$api_class = 'GovPay\Pendenze\Api\PendenzeApi'; 

echo "--- Verifica del Client GovPay/Pagamenti ---\n";

if (class_exists($api_class)) {
    echo "✅ SUCCESSO: La classe API è stata trovata.\n";
    echo "Namespace: $api_class\n";
    
    // Puoi anche provare a instanziare l'oggetto per un test più completo
    try {
        $client = new $api_class(
            new GuzzleHttp\Client(), // Richiede Guzzle (dovrebbe essere in require del client)
            new GovPay\Pagamenti\Configuration() // Classe di configurazione standard generata
        );
        echo "✅ Istanza creata con successo.\n";
    } catch (\Throwable $e) {
        echo "⚠️ ATTENZIONE: La classe esiste, ma l'istanziazione ha generato un errore.\n";
        echo "Controlla le dipendenze di Guzzle nel vendor.\n";
    }
    
} else {
    echo "❌ FALLIMENTO: La classe $api_class NON è stata trovata.\n";
    echo "Controlla i percorsi PSR-4 nei composer.json dei client generati.\n";
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
