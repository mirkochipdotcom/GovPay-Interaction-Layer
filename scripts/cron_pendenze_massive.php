<?php
declare(strict_types=1);

/**
 * Script per elaborare in background l'inserimento massivo di pendenze.
 * Deve essere richiamato via cron o systemd timer (CLI).
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Database\MassivePendenzeRepository;
use App\Controllers\PendenzeController;
use Dotenv\Dotenv;
use Slim\Views\Twig;

// Nel container GovPay, le env (incluso DB_NAME ecc.) sono esposte ad apache.
// Per gli script CLI, le si può iniettare leggendo .env o dal docker-compose.
if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
}

// Fallback DB_HOST a "db" per l'ambiente Docker se non valorizzato e non in localhost
if (!getenv('DB_HOST')) {
    putenv("DB_HOST=db");
}

// Imposta un time limit compatibile con lo script
set_time_limit(300);

echo "[".date('Y-m-d H:i:s')."] Avvio batch elaborazione pendenze massive...\n";

// Istanze necessarie
$repo = new MassivePendenzeRepository();

// Per inviare la pendenza, usiamo la logica esistente nel PendenzeController.
// Siccome la classe controller si aspetta Twig, lo simuliamo.
$twigMock = Twig::create(dirname(__DIR__) . '/backoffice/templates');
$controller = new PendenzeController($twigMock, null);

// Prendi i primi N (es. 50 righe alla volta)
$batchSize = 50;
$pending = $repo->fetchPending($batchSize);

if (count($pending) === 0) {
    echo "[".date('Y-m-d H:i:s')."] Nessuna pendenza in stato PENDING trovata.\n";
    exit(0);
}

echo "[".date('Y-m-d H:i:s')."] Trovate ".count($pending)." pendenze in coda.\n";

foreach ($pending as $row) {
    $id = (int)$row['id'];
    $batchId = $row['file_batch_id'];
    $numeroRiga = (int)$row['riga'];
    
    // Decodifica il payload salvato
    $payloadJson = $row['payload_json'];
    $payload = $payloadJson ? json_decode($payloadJson, true) : null;

    if (!is_array($payload)) {
        echo "  [Riga $batchId:$numeroRiga] ID-$id ERRORE PAYLOAD SCORRETTO.\n";
        $repo->setResult($id, false, null, "Payload JSON non valido o mancante");
        continue;
    }

    echo "  [Riga $batchId:$numeroRiga] Elaborazione pendenza ID-$id ...";
    $repo->setProcessing($id); // Segna la transizione a PROCESSING

    // Arricchimento dati contabili (IBAN, codEntrata, tipoBollo, etc)
    $accErrors = [];
    $accWarnings = [];
    $accountingParams = [
        'idDominio' => $payload['idDominio'] ?? '',
        'idTipoPendenza' => $payload['idTipoPendenza'] ?? ''
    ];
    $payload['voci'] = $controller->buildVociWithAccounting(
        $payload['voci'] ?? [],
        $accountingParams,
        null,
        $accErrors,
        $accWarnings
    );

    if (!empty($accErrors)) {
        $errorMsg = "Errore contabilità: " . implode("; ", $accErrors);
        echo " ERRORE ($errorMsg)\n";
        $repo->setResult($id, false, null, $errorMsg);
        continue;
    }

    // Aggiunta Origine e Log in DatiAllegati per i filtri pendenze
    $dDa = isset($payload['datiAllegati']) && is_string($payload['datiAllegati']) ? json_decode($payload['datiAllegati'], true) : ($payload['datiAllegati'] ?? []);
    if (!is_array($dDa)) $dDa = [];
    $dDa['sorgente'] = 'GIL-Massivo';
    $payload['datiAllegati'] = $dDa;

    // Creiamo una pendenza con \App\Controllers\PendenzeController::sendPendenzaToBackoffice
    // Togliamo la chiave " idPendenza " se ne generasse uno per lasciare che backoffice
    // API la crei se mancante, in realtà se la UI massivo non lo passa, la funzione helper lo creerà.
    $res = $controller->sendPendenzaToBackoffice($payload);

    if ($res['success'] === true) {
        echo " OK (Creato: " . ($res['idPendenza'] ?? 'sconosciuto') . ")\n";
        $repo->setResult($id, true, $res['response'] ?? null, null);
    } else {
        $errorMsg = is_array($res['errors']) ? implode("; ", $res['errors']) : 'Errore sconosciuto';
        echo " ERRORE ($errorMsg)\n";
        $repo->setResult($id, false, $res['response'] ?? null, $errorMsg);
    }
}

echo "[".date('Y-m-d H:i:s')."] Batch concluso.\n";
