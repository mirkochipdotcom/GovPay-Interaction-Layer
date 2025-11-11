<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database\Connection;
use App\Database\EntrateRepository;
use App\Database\MassivePendenzeRepository;
use App\Services\ValidationService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class MassivePendenzeController
{
    public function __construct(private readonly Twig $twig)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        $this->exposeCurrentUser();
        // Lista batches (aggregati) per tabella stato
        $pdo = Connection::getPDO();
        $sql = "SELECT file_batch_id,
                       COUNT(*) AS totale,
                       SUM(stato='PENDING') AS pending,
                       SUM(stato='PROCESSING') AS processing,
                       SUM(stato='SUCCESS') AS success,
                       SUM(stato='ERROR') AS error
                FROM pendenze_massive
                GROUP BY file_batch_id
                ORDER BY MAX(created_at) DESC
                LIMIT 50";
        try {
            $batches = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $batches = [];
        }
        // Tipologie per select (se presenti nel DB)
        $tipologie = [];
        $idDominio = getenv('ID_DOMINIO') ?: '';
        if ($idDominio) {
            try { $repo = new EntrateRepository(); $tipologie = $repo->listAbilitateByDominio($idDominio); } catch (\Throwable $e) {}
        }
        return $this->twig->render($response, 'pendenze/inserimento_massivo.html.twig', [
            'batch_list' => $batches,
            'tipologie_pendenze' => $tipologie,
        ]);
    }

    public function templateCsv(Request $request, Response $response): Response
    {
        $headers = [
            'TIPO','CODICE_FISCALE_PIVA','COGNOME_ANAGRAFICA','NOME','CAUSALE','ANNO_RIFERIMENTO','IMPORTO','EMAIL','DATA_VALIDITA','DATA_SCADENZA','RATA','VOCE_1_IMPORTO','VOCE_1_CAUSALE','VOCE_2_IMPORTO','VOCE_2_CAUSALE'
        ];
        $sample = ['F','RSSMRA80A01F205X','ROSSI','MARIO','TASSA ISCRIZIONE',date('Y'), '44.00','mario.rossi@example.com','', '', '', '44.00','Quota iscrizione','',''];
        $csv = fopen('php://temp', 'w+');
        // separatore ;
        fputcsv($csv, $headers, ';');
        fputcsv($csv, $sample, ';');
        rewind($csv);
        $content = stream_get_contents($csv) ?: '';
        fclose($csv);
        $response->getBody()->write($content);
        return $response
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="template_pendenze_massive.csv"');
    }

    public function upload(Request $request, Response $response): Response
    {
        $this->exposeCurrentUser();
        $files = $request->getUploadedFiles();
        $file = $files['csv_file'] ?? null;
        $errors = [];
        if (!$file) {
            $errors[] = 'File CSV mancante';
            return $this->twig->render($response, 'pendenze/inserimento_massivo.html.twig', ['errors' => $errors]);
        }
        if ($file->getError() !== UPLOAD_ERR_OK) {
            $errors[] = 'Errore upload file: ' . $file->getError();
            return $this->twig->render($response, 'pendenze/inserimento_massivo.html.twig', ['errors' => $errors]);
        }
        $stream = $file->getStream();
        $content = $stream->getContents();
        $rows = self::parseCsv($content);
        if (count($rows) === 0) {
            $errors[] = 'CSV vuoto o non leggibile';
            return $this->twig->render($response, 'pendenze/inserimento_massivo.html.twig', ['errors' => $errors]);
        }
        // Normalizza header
        $header = array_map(fn($h) => strtoupper(trim((string)$h)), array_shift($rows));
        $expected = ['TIPO','CODICE_FISCALE_PIVA','COGNOME_ANAGRAFICA','NOME','CAUSALE','ANNO_RIFERIMENTO','IMPORTO','EMAIL','DATA_VALIDITA','DATA_SCADENZA','RATA','VOCE_1_IMPORTO','VOCE_1_CAUSALE','VOCE_2_IMPORTO','VOCE_2_CAUSALE'];
        if ($header !== $expected) {
            $errors[] = 'Intestazioni CSV non valide. Atteso: ' . implode(';', $expected);
            return $this->twig->render($response, 'pendenze/inserimento_massivo.html.twig', ['errors' => $errors]);
        }
        // Processa righe: valida e costruisci preview
        $valid = [];
        $invalid = [];
        foreach ($rows as $i => $row) {
            $rowNum = $i + 2; // header è riga 1
            $r = self::assocRow($header, $row);
            $norm = self::normalizeRow($r);
            $err = self::validateRow($norm);
            if ($err) {
                $norm['_error'] = $err;
                $norm['_row'] = $rowNum;
                $invalid[] = $norm;
            } else {
                $norm['_row'] = $rowNum;
                $valid[] = $norm;
            }
        }
        // Assign batch id and persist invalids as ERROR records (optional) or keep in memory for CSV error download
        $batchId = 'B' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
        // Persist just for listing counts (we will insert valid ones only on confirm step)
        $repo = new MassivePendenzeRepository();
        foreach ($invalid as $inv) {
            $payload = $inv; unset($payload['_error'],$payload['_row']);
            $repo->insertPending($batchId, (int)$inv['_row'], $payload, $inv['_error']);
        }
        // keep first 2 valids for preview
        $firstValid = array_slice($valid, 0, 2);
        // cache valid rows into session for later confirm (simple approach)
        $_SESSION['massive_valid_rows'][$batchId] = $valid;
        // Tipologie
        $tipologie = [];
        $idDominio = getenv('ID_DOMINIO') ?: '';
        if ($idDominio) { try { $tipologie = (new EntrateRepository())->listAbilitateByDominio($idDominio); } catch (\Throwable $e) {} }
        return $this->twig->render($response, 'pendenze/inserimento_massivo.html.twig', [
            'preview' => [
                'batch_id' => $batchId,
                'total_count' => count($rows),
                'valid_count' => count($valid),
                'invalid_count' => count($invalid),
                'first_valid' => $firstValid,
            ],
            'tipologie_pendenze' => $tipologie,
        ]);
    }

    public function downloadErroriCsv(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $batchId = $params['batch'] ?? '';
        if ($batchId === '') return $response->withStatus(400);
        $pdo = Connection::getPDO();
        $stmt = $pdo->prepare("SELECT riga, payload_json, errore FROM pendenze_massive WHERE file_batch_id = :b AND stato='ERROR' ORDER BY riga ASC");
        $stmt->execute([':b' => $batchId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $csv = fopen('php://temp', 'w+');
        $headers = ['RIGA','MOTIVO','PAYLOAD'];
        fputcsv($csv, $headers, ';');
        foreach ($rows as $r) {
            fputcsv($csv, [$r['riga'], $r['errore'], $r['payload_json']], ';');
        }
        rewind($csv); $content = stream_get_contents($csv) ?: ''; fclose($csv);
        $response->getBody()->write($content);
        return $response->withHeader('Content-Type','text/csv; charset=UTF-8')
                        ->withHeader('Content-Disposition','attachment; filename="errori_batch_' . $batchId . '.csv"');
    }

    public function conferma(Request $request, Response $response): Response
    {
        $this->exposeCurrentUser();
        $params = (array)($request->getParsedBody() ?? []);
        $batchId = trim((string)($params['batch_id'] ?? ''));
        $idTipo = trim((string)($params['idTipoPendenza'] ?? ''));
        if ($batchId === '' || $idTipo === '') {
            return $response->withStatus(400);
        }
        $valid = $_SESSION['massive_valid_rows'][$batchId] ?? [];
        if (!is_array($valid) || count($valid) === 0) {
            return $response->withStatus(400);
        }
        $repo = new MassivePendenzeRepository();
        $riga = 0;
        foreach ($valid as $v) {
            $riga = (int)($v['_row'] ?? ++$riga);
            $payload = self::rowToPayload($v, $idTipo);
            $repo->insertPending($batchId, $riga, $payload, null);
        }
        // redirect alla lista dettagli batch
        return $response->withHeader('Location', '/pendenze/massivo/dettaglio?batch=' . urlencode($batchId))->withStatus(302);
    }

    public function dettaglio(Request $request, Response $response): Response
    {
        $this->exposeCurrentUser();
        $params = $request->getQueryParams();
        $batchId = $params['batch'] ?? '';
        if ($batchId === '') return $response->withStatus(400);
        $repo = new MassivePendenzeRepository();
        $rows = $repo->listByBatch($batchId, null, 1, 500);
        return $this->twig->render($response, 'pendenze/massivo_dettaglio.html.twig', [
            'batch_id' => $batchId,
            'rows' => $rows,
        ]);
    }

    private function exposeCurrentUser(): void
    {
        // same helper as other controllers if needed (left minimal here)
    }

    private static function parseCsv(string $content): array
    {
        $rows = [];
        $fh = fopen('php://temp', 'w+');
        fwrite($fh, $content); rewind($fh);
        while (($r = fgetcsv($fh, 0, ';')) !== false) { $rows[] = $r; }
        fclose($fh);
        return $rows;
    }

    private static function assocRow(array $header, array $row): array
    {
        $out = [];
        foreach ($header as $i => $key) { $out[$key] = $row[$i] ?? ''; }
        return $out;
    }

    private static function normalizeRow(array $r): array
    {
        $tipo = strtoupper(trim((string)($r['TIPO'] ?? 'F')));
        $ident = strtoupper(trim((string)($r['CODICE_FISCALE_PIVA'] ?? '')));
        $cogn = strtoupper(trim((string)($r['COGNOME_ANAGRAFICA'] ?? '')));
        $nome = strtoupper(trim((string)($r['NOME'] ?? '')));
        $caus = (string)($r['CAUSALE'] ?? '');
        $anno = (int)($r['ANNO_RIFERIMENTO'] ?? 0);
        $imp = (float)str_replace(',', '.', (string)($r['IMPORTO'] ?? '0'));
        $email = trim((string)($r['EMAIL'] ?? ''));
        $dv = trim((string)($r['DATA_VALIDITA'] ?? ''));
        $ds = trim((string)($r['DATA_SCADENZA'] ?? ''));
        $rata = trim((string)($r['RATA'] ?? ''));
        $v1imp = (string)($r['VOCE_1_IMPORTO'] ?? '');
        $v1desc = (string)($r['VOCE_1_CAUSALE'] ?? '');
        $v2imp = (string)($r['VOCE_2_IMPORTO'] ?? '');
        $v2desc = (string)($r['VOCE_2_CAUSALE'] ?? '');
        return [
            'tipo' => $tipo,
            'identificativo' => $ident,
            'anagrafica' => $cogn,
            'nome' => $nome,
            'causale' => $caus,
            'annoRiferimento' => $anno,
            'importo' => $imp,
            'email' => $email,
            'dataValidita' => $dv,
            'dataScadenza' => $ds,
            'rata' => $rata,
            'voci' => [
                ['idVocePendenza' => '1', 'descrizione' => $v1desc ?: $caus, 'importo' => ($v1imp !== '' ? (float)str_replace(',', '.', $v1imp) : $imp)],
                ['idVocePendenza' => '2', 'descrizione' => $v2desc, 'importo' => ($v2imp !== '' ? (float)str_replace(',', '.', $v2imp) : 0)],
            ],
        ];
    }

    private static function validateRow(array $norm): ?string
    {
        // Required fields
        foreach (['tipo','identificativo','anagrafica','causale','annoRiferimento','importo','email'] as $f) {
            if ($f !== 'email' && (empty($norm[$f]) && $norm[$f] !== 0 && $norm[$f] !== '0')) {
                return 'Campo obbligatorio mancante: ' . $f;
            }
        }
        // Causale length
        if (!ValidationService::validateCausaleLength($norm['causale'])) return 'Causale oltre 140 caratteri';
        // Tipo
        if (!in_array($norm['tipo'], ['F','G'], true)) return 'TIPO non valido (F/G)';
        // Identificativo
        if ($norm['tipo'] === 'F') {
            $res = ValidationService::validateCodiceFiscale($norm['identificativo'], $norm['nome'] ?: null, $norm['anagrafica'] ?: null);
            if (!$res['format_ok'] || !$res['check_ok']) return $res['message'] ?: 'Codice fiscale non valido';
        } else {
            $res = ValidationService::validatePartitaIva($norm['identificativo']);
            if (!$res['valid']) return $res['message'] ?: 'Partita IVA non valida';
        }
        // Importo
        if (!is_numeric((string)$norm['importo']) || $norm['importo'] <= 0) return 'Importo non valido';
        // Voci sum
        $sum = 0.0; foreach ($norm['voci'] as $v) { $sum += (float)($v['importo'] ?? 0); }
        if ((int)round($sum * 100) !== (int)round(((float)$norm['importo']) * 100)) return 'Somma voci diversa da importo';
        return null;
    }

    private static function rowToPayload(array $norm, string $idTipoPendenza): array
    {
        $payload = [
            'idTipoPendenza' => $idTipoPendenza,
            'idDominio' => getenv('ID_DOMINIO') ?: '',
            'causale' => $norm['causale'],
            'importo' => (float)$norm['importo'],
            'annoRiferimento' => (int)$norm['annoRiferimento'],
            'soggettoPagatore' => [
                'tipo' => $norm['tipo'],
                'identificativo' => $norm['identificativo'],
                'anagrafica' => ($norm['tipo']==='F' && !empty($norm['nome'])) ? (trim($norm['nome'] . ' ' . $norm['anagrafica'])) : $norm['anagrafica'],
            ],
            'voci' => array_values(array_filter($norm['voci'], fn($v) => (float)($v['importo'] ?? 0) > 0)),
        ];
        if (!empty($norm['email'])) $payload['soggettoPagatore']['email'] = $norm['email'];
        if (!empty($norm['dataValidita'])) $payload['dataValidita'] = $norm['dataValidita'];
        if (!empty($norm['dataScadenza'])) $payload['dataScadenza'] = $norm['dataScadenza'];
        return $payload;
    }
}
