<?php
// Controlla il codice di stato originale che ha causato il reindirizzamento.
$original_status = $_SERVER['REDIRECT_STATUS'] ?? 404;

// Se l'errore originale era un 403 (Forbidden)...
if ($original_status === 403) {
    // ...forziamo la risposta HTTP a 404 Not Found.
    http_response_code(404);
    $error_message = "La risorsa richiesta non è stata trovata.";
    $title = "404 Not Found";
} else {
    // Altrimenti (è 404 o un altro errore), usiamo il 404 standard.
    http_response_code(404);
    $error_message = "La risorsa richiesta non è stata trovata.";
    $title = "404 Not Found";
}

// Interfaccia visuale (puoi sostituire questo HTML con il tuo template)
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <style>
        body { font-family: sans-serif; text-align: center; padding-top: 50px; }
        h1 { color: #cc0000; }
    </style>
</head>
<body>
    <h1><?= $title ?></h1>
    <p><?= $error_message ?></p>
    <p>La risorsa che cercavi potrebbe essere stata rimossa o non è mai esistita.</p>
</body>
</html>
