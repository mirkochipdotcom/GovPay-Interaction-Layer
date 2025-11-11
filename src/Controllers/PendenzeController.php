<?php
/**
 * SPDX-License-Identifier: EUPL-1.2
 * License: European Union Public Licence v1.2 (EUPL-1.2)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Database\EntrateRepository;
use App\Logger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GovPay\Backoffice\Api\PendenzeApi as BackofficePendenzeApi;
use GovPay\Backoffice\Configuration as BackofficeConfiguration;
use GovPay\Backoffice\Model\RaggruppamentoStatistica;
use GovPay\Backoffice\Model\StatoPendenza;
use GovPay\Backoffice\ObjectSerializer as BackofficeSerializer;
use GovPay\Pendenze\Api\PendenzeApi;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Services\TracciatoService;
use App\Services\ValidationService;

class PendenzeController
{
    public function __construct(private readonly Twig $twig, private ?TracciatoService $tracciatoService = null)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        // Se la route riceve una POST, la trattiamo come richiesta di creazione pendenza
        if (strtoupper((string)$request->getMethod()) === 'POST') {
            return $this->create($request, $response);
        }

        $debug = '';
        $apiClass = PendenzeApi::class;
        if (class_exists($apiClass)) {
            $debug .= "Classe trovata: {$apiClass}\n";
            try {
                $client = new Client();
                new PendenzeApi($client, new PendenzeConfiguration());
                $debug .= "Istanza API creata con successo.\n";
            } catch (\Throwable $e) {
                $debug .= 'Errore: ' . $e->getMessage() . "\n";
            }
        } else {
            $debug .= "Classe API non trovata.\n";
        }

    $errors = [];
    $warnings = [];
        $statsJson = null;
        $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
        if (class_exists(BackofficePendenzeApi::class)) {
            if (!empty($backofficeUrl)) {
                try {
                    $config = new BackofficeConfiguration();
                    $config->setHost(rtrim($backofficeUrl, '/'));

                    $username = getenv('GOVPAY_USER');
                    $password = getenv('GOVPAY_PASSWORD');
                    if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                        $config->setUsername($username);
                        $config->setPassword($password);
                    }

                    $guzzleOptions = [];
                    $authMethod = getenv('AUTHENTICATION_GOVPAY');
                    if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
                        $cert = getenv('GOVPAY_TLS_CERT');
                        $key = getenv('GOVPAY_TLS_KEY');
                        $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
                        if (!empty($cert) && !empty($key)) {
                            $guzzleOptions['cert'] = $cert;
                            $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
                        } else {
                            $errors[] = 'mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati';
                        }
                    }

                    $httpClient = new Client($guzzleOptions);
                    $api = new BackofficePendenzeApi($httpClient, $config);

                    $gruppi = [RaggruppamentoStatistica::DOMINIO];
                    $idDominioEnv = getenv('ID_DOMINIO');
                    if ($idDominioEnv !== false && $idDominioEnv !== '') {
                        $stats = $api->findQuadratureRiscossioni($gruppi, 1, 10, null, null, trim((string)$idDominioEnv));
                    } else {
                        $stats = $api->findQuadratureRiscossioni($gruppi, 1, 10);
                    }

                    $data = BackofficeSerializer::sanitizeForSerialization($stats);
                    $statsJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                } catch (\Throwable $e) {
                    $errors[] = 'Errore chiamata Backoffice: ' . $e->getMessage();
                }
            } else {
                $errors[] = 'Variabile GOVPAY_BACKOFFICE_URL non impostata';
            }
        } else {
            $errors[] = 'Client Backoffice non disponibile (namespace GovPay\\Backoffice)';
        }

        $this->exposeCurrentUser();

        return $this->twig->render($response, 'pendenze.html.twig', [
            'debug' => nl2br(htmlspecialchars($debug, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
            'stats_json' => $statsJson,
            'errors' => $errors,
        ]);
    }

    /**
     * Gestisce la creazione di una nuova pendenza inviata dal form.
     */
    public function create(Request $request, Response $response): Response
    {
        $this->exposeCurrentUser();

    $params = (array)($request->getParsedBody() ?? []);
    $errors = [];
    $warnings = [];

        // idA2A: deve essere preso esclusivamente dalla variabile d'ambiente
        $idA2A = getenv('ID_A2A') ?: '';
        if ($idA2A === '') {
            $errors[] = 'Variabile di ambiente ID_A2A non impostata';
        }

        // Campi principali
        $idTipo = trim((string)($params['idTipoPendenza'] ?? ''));
        $causale = trim((string)($params['causale'] ?? ''));
        $importoRaw = $params['importo'] ?? '';
        $anno = $params['annoRiferimento'] ?? null;

        if ($idTipo === '') {
            $errors[] = 'Tipologia pendenza obbligatoria';
        }
        if ($causale === '') {
            $errors[] = 'Causale obbligatoria';
        } elseif (!ValidationService::validateCausaleLength($causale)) {
            $errors[] = 'La causale supera i 140 caratteri consentiti';
        }
        if ($importoRaw === '' || !is_numeric(str_replace(',', '.', (string)$importoRaw))) {
            $errors[] = 'Importo non valido';
        }
        $importo = (float)str_replace(',', '.', (string)$importoRaw);
        if ($anno === null || !is_numeric((string)$anno)) {
            $errors[] = 'Anno di riferimento non valido';
        } else {
            $anno = (int)$anno;
        }

        // Soggetto
        $sog = $params['soggettoPagatore'] ?? [];
        if (!is_array($sog)) $sog = [];
        $tipoSog = strtoupper((string)($sog['tipo'] ?? 'F'));
        if (!in_array($tipoSog, ['F', 'G'], true)) {
            $errors[] = 'Tipo soggetto non valido';
        }
    $identificativo = strtoupper(trim((string)($sog['identificativo'] ?? '')));
    $anagrafica = strtoupper(trim((string)($sog['anagrafica'] ?? '')));
    $nome = strtoupper(trim((string)($sog['nome'] ?? '')));
        $email = trim((string)($sog['email'] ?? ''));
        if ($identificativo === '') $errors[] = 'Codice fiscale / Partita IVA obbligatorio';
        if ($anagrafica === '') $errors[] = ($tipoSog === 'F') ? 'Cognome obbligatorio' : 'Ragione sociale obbligatoria';
        // Validazioni specifiche CF/P.IVA secondo tipo soggetto
        if ($identificativo !== '') {
            if ($tipoSog === 'F') {
                $res = ValidationService::validateCodiceFiscale($identificativo, $nome, $anagrafica);
                if (!$res['format_ok'] || !$res['check_ok']) {
                    $errors[] = $res['message'] ?? 'Codice fiscale non valido';
                } elseif (!$res['name_match']) {
                    $errors[] = $res['message'] ?? 'Codice fiscale non coerente con nome e cognome indicati';
                }
            } elseif ($tipoSog === 'G') {
                $res = ValidationService::validatePartitaIva($identificativo);
                if (!$res['valid']) {
                    $errors[] = $res['message'] ?? 'Partita IVA non valida';
                }
            }
        }

        // Voci
        $vociRaw = $params['voci'] ?? [];
        $voci = [];
        if (!is_array($vociRaw) || count($vociRaw) === 0) {
            // Crea una voce di default
            $voci[] = [
                'idVocePendenza' => '1',
                'descrizione' => $causale,
                'importo' => $importo,
            ];
        } else {
            $sum = 0.0;
            foreach ($vociRaw as $k => $vr) {
                $idV = trim((string)($vr['idVocePendenza'] ?? ''));
                $desc = trim((string)($vr['descrizione'] ?? ''));
                $impRaw = $vr['importo'] ?? '';
                if ($idV === '') $errors[] = "ID voce mancante per voce #{$k}";
                if ($desc === '') $errors[] = "Descrizione voce mancante per voce #{$k}";
                if ($impRaw === '' || !is_numeric(str_replace(',', '.', (string)$impRaw))) {
                    $errors[] = "Importo voce non valido per voce #{$k}";
                    $imp = 0.0;
                } else {
                    $imp = (float)str_replace(',', '.', (string)$impRaw);
                }
                $sum += $imp;
                $voci[] = ['idVocePendenza' => $idV, 'descrizione' => $desc, 'importo' => $imp];
            }
            // Se la somma delle voci non corrisponde all'importo principale, proviamo a riallineare la prima voce
            if (abs($sum - $importo) > 0.001) {
                if (count($voci) >= 1) {
                    $other = $sum - $voci[0]['importo'];
                    $voci[0]['importo'] = max(0.0, $importo - $other);
                    $sum = 0.0; foreach ($voci as $vv) $sum += $vv['importo'];
                    if (abs($sum - $importo) > 0.001) {
                        $errors[] = 'La somma delle voci non corrisponde all\'importo totale';
                    }
                } else {
                    $errors[] = 'La somma delle voci non corrisponde all\'importo totale';
                }
            }
        }

        // Se abbiamo errori: ricarichiamo il form con i valori precedenti
        if ($errors) {
            $idDominio = getenv('ID_DOMINIO') ?: '';
            $tipologie = [];
            if ($idDominio) {
                try {
                    $repo = new EntrateRepository();
                    $tipologie = $repo->listAbilitateByDominio($idDominio);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            return $this->twig->render($response, 'pendenze/inserimento.html.twig', [
                'errors' => $errors,
                'warnings' => $warnings,
                'old' => $params,
                'tipologie_pendenze' => $tipologie,
                'id_dominio' => $idDominio,
                'id_a2a' => $idA2A,
                'default_anno' => (int)date('Y'),
            ]);
        }

        // Costruzione payload
        $payload = [
            'idTipoPendenza' => $idTipo,
            'idDominio' => $params['idDominio'] ?? (getenv('ID_DOMINIO') ?: ''),
            'causale' => $causale,
            'importo' => $importo,
            'annoRiferimento' => $anno,
            'soggettoPagatore' => [
                'tipo' => $tipoSog,
                'identificativo' => $identificativo,
                'anagrafica' => $anagrafica,
            ],
            'voci' => $voci,
        ];
        // Il Backoffice si aspetta il campo 'anagrafica' come nome e cognome insieme;
        // non inviare il campo 'nome' separato (causa UnrecognizedPropertyException).
        if ($tipoSog === 'F') {
            // Combina nome + cognome (se fornito) rispettando il formato 'Nome Cognome'
            $full = trim(((string)$nome !== '' ? ((string)$nome . ' ') : '') . (string)$anagrafica);
            $payload['soggettoPagatore']['anagrafica'] = $full !== '' ? $full : $anagrafica;
        } else {
            $payload['soggettoPagatore']['anagrafica'] = $anagrafica;
        }
        if (!empty($email)) {
            $payload['soggettoPagatore']['email'] = $email;
        }
        // Aggiorna l'identificativo normalizzato
        if ($identificativo !== '') {
            $payload['soggettoPagatore']['identificativo'] = $identificativo;
        }
        if (!empty($params['dataValidita'])) $payload['dataValidita'] = $params['dataValidita'];
        if (!empty($params['dataScadenza'])) $payload['dataScadenza'] = $params['dataScadenza'];
        foreach (['direzione', 'divisione', 'cartellaPagamento'] as $f) {
            if (!empty($params[$f])) $payload[$f] = $params[$f];
        }

        // Se il client ha fornito un 'documento' (es. per rateizzazione) lo includiamo
        if (!empty($params['documento']) && is_array($params['documento'])) {
            $payload['documento'] = $params['documento'];
        }
        // Includiamo anche eventuali proprieta' o allegati se forniti
        if (!empty($params['proprieta']) && is_array($params['proprieta'])) {
            $payload['proprieta'] = $params['proprieta'];
        }
        if (!empty($params['allegati']) && is_array($params['allegati'])) {
            $payload['allegati'] = $params['allegati'];
        }

        // Recupera dal DB i parametri associati alla tipologia (es. IBAN, codice contabile, tipo bollo)
        $idDominioUsed = $payload['idDominio'] ?? '';
        if ($idDominioUsed !== '' && $idTipo !== '') {
            try {
                $repo = new EntrateRepository();
                $details = $repo->findDetails($idDominioUsed, $idTipo);
            } catch (\Throwable $_) {
                $details = null;
            }
        } else {
            $details = null;
        }

    // Determina i valori finali dei campi di contabilita' (DB-first, poi form)
    $finalIban = '';
    $finalCodEntrata = '';
    $finalTipoBollo = '';
    $finalTipoContabilita = '';
        if ($details) {
            if (!empty($details['iban_accredito'])) $finalIban = $details['iban_accredito'];
            if (!empty($details['codice_contabilita'])) $finalCodEntrata = $details['codice_contabilita'];
            if (!empty($details['tipo_bollo'])) $finalTipoBollo = $details['tipo_bollo'];
            if (!empty($details['tipo_contabilita'])) $finalTipoContabilita = $details['tipo_contabilita'];
        }
        // Se non presenti nel DB, usa l'eventuale override dal form
        if ($finalIban === '' && !empty($params['ibanAccredito'])) $finalIban = $params['ibanAccredito'];
    // accetta sia il nome storico 'codEntrata' che il nome API 'codiceContabilita' dal form
    if ($finalCodEntrata === '' && (!empty($params['codiceContabilita']) || !empty($params['codEntrata']))) $finalCodEntrata = $params['codiceContabilita'] ?? $params['codEntrata'];

    // Per semplicità e coerenza con il sistema, il codice di contabilita' sarà
    // l'identificativo della tipologia scelta (idTipo). Questo sovrascrive
    // eventuali valori dal DB o dal form.
    if ($idTipo !== '') {
        $finalCodEntrata = (string)$idTipo;
    }
    if ($finalTipoBollo === '' && !empty($params['tipoBollo'])) $finalTipoBollo = $params['tipoBollo'];
    if ($finalTipoContabilita === '' && !empty($params['tipoContabilita'])) $finalTipoContabilita = $params['tipoContabilita'];

        // Validazione / sanitizzazione del campo codEntrata per rispettare il pattern API
        // Pattern consentito: lettere, numeri, '-', '_' e '.' fino a 35 caratteri
        $codPattern = '/^[A-Za-z0-9\-_.]{1,35}$/';
        if ($finalCodEntrata !== '') {
            if (!preg_match($codPattern, $finalCodEntrata)) {
                $orig = $finalCodEntrata;
                $sanitized = preg_replace('/[^A-Za-z0-9\-_.]/', '', $orig);
                $sanitized = substr($sanitized, 0, 35);
                if ($sanitized !== '') {
                    // Log e notifica utente (avviso)
                    Logger::getInstance()->warning("codiceContabilita sanitizzato per invio: '{$orig}' -> '{$sanitized}'", ['idTipo' => $idTipo, 'idDominio' => $idDominioUsed]);
                    $warnings[] = "Il valore codiceContabilita '{$orig}' non è valido per l'API e verrà inviato come '{$sanitized}' (caratteri non validi rimossi).";
                    $finalCodEntrata = $sanitized;
                } else {
                    $errors[] = 'Il valore [' . $orig . '] del campo codiceContabilita non rispetta il pattern richiesto: (^[a-zA-Z0-9\\-_\\.]{1,35}$)';
                }
            }
        }
        // Se sia IBAN che codice entrata sono presenti, li inviamo entrambi come
        // Entrata completa (l'API v2 richiede ibanAccredito + tipoContabilita + codiceContabilita).
            // Decide quale rappresentazione inviare per le voci:
            // - Bollo: se è valorizzato tipoBollo
            // - Entrata (completa): se sono presenti IBAN, tipoContabilita e codiceContabilita
            // - RiferimentoEntrata (fallback): se manca la Entrata completa ma è presente il codice entrata
            // Altrimenti generiamo un errore.
            $voiceMode = null; // 'bollo'|'entrata'|'riferimento'
            if ($finalTipoBollo !== '') {
                $voiceMode = 'bollo';
            } else {
                $hasEntrata = ($finalIban !== '' && $finalTipoContabilita !== '' && $finalCodEntrata !== '');
                if ($hasEntrata) {
                    $voiceMode = 'entrata';
                    Logger::getInstance()->info('Entrata completa trovata: invio Entrata', ['idTipo' => $idTipo, 'idDominio' => $idDominioUsed, 'iban' => $finalIban, 'tipoContabilita' => $finalTipoContabilita, 'codiceContabilita' => $finalCodEntrata]);
                    $warnings[] = 'La tipologia contiene dati completi di contabilita: verrà inviata una Entrata completa.';
                } elseif ($finalCodEntrata !== '') {
                    $voiceMode = 'riferimento';
                    Logger::getInstance()->info('Fallback a RiferimentoEntrata: invio codEntrata', ['idTipo' => $idTipo, 'idDominio' => $idDominioUsed, 'codEntrata' => $finalCodEntrata]);
                    $warnings[] = 'La tipologia non contiene dati completi di Entrata: verrà inviato un riferimento alla entrata (RiferimentoEntrata).';
                } else {
                    $voiceMode = null;
                }
            }

            // Costruisce le voci secondo la rappresentazione scelta
            $builtVoci = [];
            foreach ($voci as $vv) {
                $nv = $vv; // base
                // rimuoviamo eventuali chiavi residue
                unset($nv['codiceContabilita'], $nv['codEntrata'], $nv['ibanAccredito'], $nv['tipoContabilita'], $nv['tipoBollo'], $nv['contabilita']);
                if ($voiceMode === 'bollo') {
                    if ($finalTipoBollo !== '') $nv['tipoBollo'] = $finalTipoBollo;
                } elseif ($voiceMode === 'entrata') {
                    $nv['ibanAccredito'] = $finalIban;
                    $nv['tipoContabilita'] = $finalTipoContabilita;
                    $nv['codiceContabilita'] = $finalCodEntrata;
                } elseif ($voiceMode === 'riferimento') {
                    $nv['codEntrata'] = $finalCodEntrata;
                }
                $builtVoci[] = $nv;
            }
            $payload['voci'] = $builtVoci;

            // Se non abbiamo determinato alcuna rappresentazione valida -> errore
            if ($voiceMode === null) {
                $errors[] = 'Per la voce è necessario inviare o i dati completi di Entrata (IBAN, tipoContabilita, codiceContabilita), o il riferimento alla entrata (codEntrata), o i dati di Bollo. Configura la tipologia o inserisci i valori nei campi avanzati.';
            }

        // (La costruzione delle voci è stata gestita sopra in base a $voiceMode)

        // Invio al Backoffice
        $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
        if (empty($backofficeUrl)) {
            $errors[] = 'Variabile GOVPAY_BACKOFFICE_URL non impostata';
            $idDominio = getenv('ID_DOMINIO') ?: '';
            $tipologie = [];
            if ($idDominio) {
                try {
                    $repo = new EntrateRepository();
                    $tipologie = $repo->listAbilitateByDominio($idDominio);
                } catch (\Throwable $e) {}
            }
                return $this->twig->render($response, 'pendenze/inserimento.html.twig', [
                    'errors' => $errors,
                    'warnings' => $warnings,
                    'old' => $params,
                    'tipologie_pendenze' => $tipologie,
                    'id_dominio' => $idDominio,
                    'default_anno' => (int)date('Y'),
                ]);
        }

        $guzzleOptions = [];
        $authMethod = getenv('AUTHENTICATION_GOVPAY');
        if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
            $cert = getenv('GOVPAY_TLS_CERT');
            $key = getenv('GOVPAY_TLS_KEY');
            $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
            if (!empty($cert) && !empty($key)) {
                $guzzleOptions['cert'] = $cert;
                $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
            } else {
                $errors[] = 'mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati';
                $idDominio = getenv('ID_DOMINIO') ?: '';
                $tipologie = [];
                if ($idDominio) {
                    try {
                        $repo = new EntrateRepository();
                        $tipologie = $repo->listAbilitateByDominio($idDominio);
                    } catch (\Throwable $e) {}
                }
                return $this->twig->render($response, 'pendenze/inserimento.html.twig', [
                    'errors' => $errors,
                    'warnings' => $warnings,
                    'old' => $params,
                    'tipologie_pendenze' => $tipologie,
                    'id_dominio' => $idDominio,
                    'id_a2a' => $idA2A,
                    'default_anno' => (int)date('Y'),
                ]);
            }
        }

        // Invia al Backoffice tramite helper
        $idPendenzaRaw = trim((string)($params['idPendenza'] ?? '')) ?: null;
        $sendResult = $this->sendPendenzaToBackoffice($payload, $idPendenzaRaw);
        if ($sendResult['success']) {
            // Mostra eventuali avvisi di sanitizzazione prima del messaggio di successo
            if (!empty($warnings)) {
                foreach ($warnings as $w) {
                    $_SESSION['flash'][] = ['type' => 'warning', 'text' => $w];
                }
            }
            $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Pendenza creata con successo'];
            $newId = $sendResult['idPendenza'] ?? null;
            if ($newId) {
                $base = '/pendenze/dettaglio/' . rawurlencode((string)$newId);
                $query = ['from' => 'insert'];
                if (!empty($params['return'])) {
                    $query['return'] = $params['return'];
                }
                $location = $base . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
                return $response->withHeader('Location', $location)->withStatus(302);
            }
            return $response->withHeader('Location', '/pendenze/ricerca')->withStatus(302);
        }
        // Se l'invio fallisce, copia gli errori restituiti
        foreach ($sendResult['errors'] as $e) {
            $errors[] = $e;
        }

        // In caso di errore durante l'invio, ricarichiamo il form con gli errori
        $idDominio = getenv('ID_DOMINIO') ?: '';
        $tipologie = [];
        if ($idDominio) {
            try {
                $repo = new EntrateRepository();
                $tipologie = $repo->listAbilitateByDominio($idDominio);
            } catch (\Throwable $e) {}
        }
            return $this->twig->render($response, 'pendenze/inserimento.html.twig', [
                'errors' => $errors,
                'warnings' => $warnings,
                'old' => $params,
                'tipologie_pendenze' => $tipologie,
                'id_dominio' => $idDominio,
                'id_a2a' => $idA2A,
                'default_anno' => (int)date('Y'),
            ]);
    }

    public function search(Request $request, Response $response): Response
    {
        
        $params = (array)($request->getQueryParams() ?? []);
        $errors = [];

        // Recupero tipologie pendenze abilitate
        $idDominio = $filters['idDominio'] ?? (getenv('ID_DOMINIO') ?: '');
        $tipologie = [];
        if ($idDominio) {
            $repo = new EntrateRepository();
            $tipologie = $repo->listAbilitateByDominio($idDominio);
        }

        $allowedStates = class_exists(StatoPendenza::class)
            ? StatoPendenza::getAllowableEnumValues()
            : [];

        $ordinamento = $request->getQueryParams()['ordinamento'] ?? '-dataCaricamento';


        $filters = [
            'q' => isset($params['q']) ? (string)$params['q'] : null,
            'pagina' => max(1, (int)($params['pagina'] ?? 1)),
            'risultatiPerPagina' => min(200, max(1, (int)($params['risultatiPerPagina'] ?? 25))),
            'ordinamento' => $ordinamento,
            'idDominio' => (string)($params['idDominio'] ?? (getenv('ID_DOMINIO') ?: '')),
            'idA2A' => (string)($params['idA2A'] ?? (getenv('ID_A2A') ?: '')),
            'idPendenza' => (string)($params['idPendenza'] ?? ''),
            'idDebitore' => (string)($params['idDebitore'] ?? ''),
            'stato' => (string)($params['stato'] ?? ''),
            'idPagamento' => (string)($params['idPagamento'] ?? ''),
            'dataDa' => (string)($params['dataDa'] ?? ''),
            'dataA' => (string)($params['dataA'] ?? ''),
            'direzione' => (string)($params['direzione'] ?? ''),
            'divisione' => (string)($params['divisione'] ?? ''),
            'iuv' => (string)($params['iuv'] ?? ''),
            'tipologiaPendenza' => (string)($params['tipologiaPendenza'] ?? ''),
        ];

        // Ricavo idEntrata dal filtro tipologiaPendenza
        $idEntrata = $filters['tipologiaPendenza'] ?? null;

        $validFields = ['dataCaricamento', 'dataValidita', 'dataScadenza', 'stato'];

        $normalizeOrder = static function (string $value, array $allowedFields): string {
            $value = trim($value);
            $direction = null;
            if ($value !== '') {
                $first = $value[0];
                if ($first === '+' || $first === '-') {
                    $direction = $first;
                    $value = substr($value, 1);
                }
            }
            $field = ltrim($value, '+-');
            if ($field === '' || !in_array($field, $allowedFields, true)) {
                $field = 'dataCaricamento';
            }
            if ($direction === null) {
                $direction = '+';
            }
            return $direction . $field;
        };

        $filters['ordinamento'] = $normalizeOrder((string)($filters['ordinamento'] ?? ''), $validFields);

        if ($filters['stato'] !== '' && !in_array($filters['stato'], $allowedStates, true)) {
            $errors[] = 'Valore "stato" non valido';
            $filters['stato'] = '';
        }

        $results = null;
        $numPagine = null;
        $numRisultati = null;
        $queryMade = false;
        $prevUrl = null;
        $nextUrl = null;

        if (($filters['q'] ?? null) !== null) {
            $queryMade = true;
            $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
            if (!class_exists(BackofficePendenzeApi::class)) {
                $errors[] = 'Client Backoffice non disponibile (namespace GovPay\\Backoffice)';
            } elseif (empty($backofficeUrl)) {
                $errors[] = 'Variabile GOVPAY_BACKOFFICE_URL non impostata';
            } else {
                try {
                    $config = new BackofficeConfiguration();
                    $config->setHost(rtrim($backofficeUrl, '/'));

                    $username = getenv('GOVPAY_USER');
                    $password = getenv('GOVPAY_PASSWORD');
                    if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                        $config->setUsername($username);
                        $config->setPassword($password);
                    }

                    $guzzleOptions = [];
                    $authMethod = getenv('AUTHENTICATION_GOVPAY');
                    if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
                        $cert = getenv('GOVPAY_TLS_CERT');
                        $key = getenv('GOVPAY_TLS_KEY');
                        $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
                        if (!empty($cert) && !empty($key)) {
                            $guzzleOptions['cert'] = $cert;
                            $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
                        } else {
                            $errors[] = 'mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati';
                        }
                    }

                    $httpClient = new Client($guzzleOptions);
                    $api = new BackofficePendenzeApi($httpClient, $config);

                    $pagina = $filters['pagina'];
                    $rpp = $filters['risultatiPerPagina'];
                    $ordinamento = $filters['ordinamento'];
                    $idDominio = $filters['idDominio'] !== '' ? $filters['idDominio'] : null;
                    $idA2A = $filters['idA2A'] !== '' ? $filters['idA2A'] : null;
                    $idDebitore = $filters['idDebitore'] !== '' ? $filters['idDebitore'] : null;
                    $stato = $filters['stato'] !== '' ? $filters['stato'] : null;
                    $idPagamento = $filters['idPagamento'] !== '' ? $filters['idPagamento'] : null;
                    $idPendenza = $filters['idPendenza'] !== '' ? $filters['idPendenza'] : null;
                    $dataDa = $filters['dataDa'] !== '' ? $filters['dataDa'] : null;
                    $dataA = $filters['dataA'] !== '' ? $filters['dataA'] : null;
                    $direzione = $filters['direzione'] !== '' ? $filters['direzione'] : null;
                    $divisione = $filters['divisione'] !== '' ? $filters['divisione'] : null;
                    $iuv = $filters['iuv'] !== '' ? $filters['iuv'] : null;
                    $idEntrata = $filters['tipologiaPendenza'] !== '' ? $filters['tipologiaPendenza'] : null;

    // ...existing code...

                    $mostraSpontanei = 'false';
                    $metadatiPaginazione = 'true';
                    $maxRisultati = 'true';

                    if ($idA2A === null || $idA2A === '') {
                        $errors[] = 'Parametro idA2A obbligatorio per la ricerca pendenze';
                    } else {
                        $url = rtrim($backofficeUrl, '/') . '/pendenze';
                        $query = [
                            'pagina' => $pagina,
                            'risultatiPerPagina' => $rpp,
                            'ordinamento' => $ordinamento,
                            'campi' => null,
                            'idDominio' => $idDominio,
                            'idA2A' => $idA2A,
                            'idDebitore' => $idDebitore,
                            'stato' => $stato,
                            'idPagamento' => $idPagamento,
                            'idPendenza' => $idPendenza,
                            'dataDa' => $dataDa,
                            'dataA' => $dataA,
                            'direzione' => $direzione,
                            'divisione' => $divisione,
                            'iuv' => $iuv,
                            'mostraSpontaneiNonPagati' => $mostraSpontanei,
                            'metadatiPaginazione' => $metadatiPaginazione,
                            'maxRisultati' => $maxRisultati,
                            'idTipoPendenza' => $idEntrata,
                        ];
                    
                        $query = array_filter($query, static fn($v) => $v !== null && $v !== '');

                        if (getenv('APP_DEBUG') && $filters['q']) {
                            error_log('[PendenzeController] GET ' . $url . '?' . http_build_query($query));
                        }

                        // Delegate the actual Backoffice call to a helper to keep logic
                        // consistent across the controller and make it easier to reuse
                        $dataArr = $this->callBackofficeFindPendenze($query);

                        $extractInt = static function (array $source, array $paths): ?int {
                            foreach ($paths as $path) {
                                $cursor = $source;
                                foreach (explode('.', $path) as $segment) {
                                    if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                                        continue 2;
                                    }
                                    $cursor = $cursor[$segment];
                                }
                                if ($cursor !== null && $cursor !== '') {
                                    return (int)$cursor;
                                }
                            }
                            return null;
                        };

                        $numPagine = $extractInt($dataArr, [
                            'numPagine',
                            'num_pagine',
                            'metaDatiPaginazione.numPagine',
                            'metaDatiPaginazione.num_pagine',
                            'metadatiPaginazione.numPagine',
                            'metadatiPaginazione.num_pagine',
                            'paginazione.numeroPagine',
                            'paginazione.numPagine',
                        ]);
                        $numRisultati = $extractInt($dataArr, [
                            'numRisultati',
                            'num_risultati',
                            'metaDatiPaginazione.numRisultati',
                            'metaDatiPaginazione.num_risultati',
                            'metadatiPaginazione.numRisultati',
                            'metadatiPaginazione.num_risultati',
                            'paginazione.numeroRisultati',
                            'paginazione.numRisultati',
                        ]);
                        if ($numPagine === null && $numRisultati !== null && $rpp > 0) {
                            $numPagine = (int)ceil($numRisultati / $rpp);
                        }
                        $results = $dataArr;

                        $basePath = $request->getUri()->getPath();
                        $qsBase = $params;
                        $qsBase['q'] = '1';
                        unset($qsBase['ordRecentiPrima']);
                        $qsBase['ordinamento'] = $filters['ordinamento'];
                        unset($qsBase['highlight']);
                        $qsBase['pagina'] = $filters['pagina'];
                        $qsBase['risultatiPerPagina'] = $filters['risultatiPerPagina'];

                        $buildUrl = static fn(array $paramSet): string => $basePath . '?' . http_build_query($paramSet, '', '&', PHP_QUERY_RFC3986);
                        $extractQueryString = static function (string $link): string {
                            $parts = parse_url($link);
                            if ($parts !== false && isset($parts['query'])) {
                                return (string)$parts['query'];
                            }
                            $pos = strpos($link, '?');
                            return $pos === false ? '' : substr($link, $pos + 1);
                        };

                        if ($filters['pagina'] > 1) {
                            $prevParams = $qsBase;
                            $prevParams['pagina'] = $filters['pagina'] - 1;
                            $prevUrl = $buildUrl($prevParams);
                        }
                        if ($numPagine !== null && $filters['pagina'] < $numPagine) {
                            $nextParams = $qsBase;
                            $nextParams['pagina'] = $filters['pagina'] + 1;
                            $nextUrl = $buildUrl($nextParams);
                        }

                        $nextLinkRaw = $results['prossimiRisultati'] ?? $results['prossimi_risultati'] ?? null;
                        if ($nextUrl === null && is_string($nextLinkRaw) && $nextLinkRaw !== '') {
                            $queryString = $extractQueryString($nextLinkRaw);
                            if ($queryString !== '') {
                                $linkParams = $qsBase;
                                parse_str($queryString, $linkQuery);
                                if (isset($linkQuery['pagina'])) {
                                    $linkParams['pagina'] = max(1, (int)$linkQuery['pagina']);
                                } elseif (isset($linkQuery['page'])) {
                                    $linkParams['pagina'] = max(1, (int)$linkQuery['page']);
                                } elseif (isset($linkQuery['offset']) && isset($linkQuery['risultati_per_pagina'])) {
                                    $perPage = (int)$linkQuery['risultati_per_pagina'];
                                    $pageFromOffset = $perPage > 0 ? (int)floor(((int)$linkQuery['offset']) / $perPage) + 1 : null;
                                    if ($pageFromOffset !== null && $pageFromOffset > 0) {
                                        $linkParams['pagina'] = $pageFromOffset;
                                    }
                                }

                                if (isset($linkQuery['risultati_per_pagina'])) {
                                    $linkParams['risultatiPerPagina'] = max(1, (int)$linkQuery['risultati_per_pagina']);
                                } elseif (isset($linkQuery['risultatiPerPagina'])) {
                                    $linkParams['risultatiPerPagina'] = max(1, (int)$linkQuery['risultatiPerPagina']);
                                }

                                if (isset($linkQuery['ordinamento'])) {
                                    $linkParams['ordinamento'] = (string)$linkQuery['ordinamento'];
                                }

                                if (($linkParams['pagina'] ?? $filters['pagina']) !== $filters['pagina']) {
                                    $nextUrl = $buildUrl($linkParams);
                                }
                            }
                        }
                    }
                } catch (ClientException $ce) {
                    $errors[] = 'Errore chiamata Pendenze: ' . $ce->getMessage();
                    $detailBody = $ce->getResponse() ? (string)$ce->getResponse()->getBody() : '';
                    if ($detailBody !== '') {
                        $errors[] = 'Dettaglio API: ' . $detailBody;
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Errore chiamata Pendenze: ' . $e->getMessage();
                }
            }
        }

        $highlightId = $params['highlight'] ?? null;
        $qsCurrent = $request->getUri()->getQuery();
    $returnUrl = '/pendenze/ricerca' . ($qsCurrent ? ('?' . $qsCurrent) : '');
    $cameFromInsert = isset($q['from']) && $q['from'] === 'insert';

        $this->exposeCurrentUser();

        return $this->twig->render($response, 'pendenze/ricerca.html.twig', [
            'filters' => $filters,
            'errors' => $errors,
            'allowed_states' => $allowedStates,
            'results' => $results,
            'num_pagine' => $numPagine,
            'num_risultati' => $numRisultati,
            'query_made' => $queryMade,
            'prev_url' => $prevUrl,
            'next_url' => $nextUrl,
            'return_url' => $returnUrl,
            'highlight_id' => $highlightId,
            'tipologie_pendenze' => $tipologie,
        ]);
    }

    public function showInsert(Request $request, Response $response): Response
    {
        $this->exposeCurrentUser();

        // If the inserimento route is called with POST (from preview->Modifica),
        // use posted params to prefill the form via `old` variable in the template.
        $posted = (array)($request->getParsedBody() ?? []);

        // Recupera tipologie abilitata per il dominio (se configurato)
        $idDominio = getenv('ID_DOMINIO') ?: '';
        $tipologie = [];
        if ($idDominio) {
            try {
                $repo = new EntrateRepository();
                $tipologie = $repo->listAbilitateByDominio($idDominio);
            } catch (\Throwable $e) {
                // Non blocchiamo la pagina: se il repository fallisce mostriamo comunque il form vuoto
                $tipologie = [];
            }
        }

        return $this->twig->render($response, 'pendenze/inserimento.html.twig', [
            'tipologie_pendenze' => $tipologie,
            'id_dominio' => $idDominio,
            'id_a2a' => getenv('ID_A2A') ?: '',
            'default_anno' => (int)date('Y'),
            'old' => $posted,
        ]);
    }

    public function showBulkInsert(Request $request, Response $response): Response
    {
        $this->exposeCurrentUser();
        return $this->twig->render($response, 'pendenze/inserimento_massivo.html.twig');
    }

    /**
     * Anteprima della pendenza prima dell'invio: rende una pagina di conferma
     */
    public function preview(Request $request, Response $response): Response
    {
        $this->exposeCurrentUser();
        $params = (array)($request->getParsedBody() ?? []);
        // Validazioni leggere: ricava campi essenziali e passa alla view di conferma
        $errors = [];
        $idTipo = trim((string)($params['idTipoPendenza'] ?? ''));
        $causale = trim((string)($params['causale'] ?? ''));
        $importo = $params['importo'] ?? '';
        $voci = $params['voci'] ?? [];
        if ($idTipo === '') $errors[] = 'Tipologia pendenza obbligatoria';
        if ($causale === '') $errors[] = 'Causale obbligatoria';
        if ($importo === '' || !is_numeric(str_replace(',', '.', (string)$importo))) $errors[] = 'Importo non valido';
        // Normalizza importo
        $importoFloat = is_numeric(str_replace(',', '.', (string)$importo)) ? (float)str_replace(',', '.', (string)$importo) : 0.0;

        // Recupera tipologia per mostrare descrizione
        $idDominio = getenv('ID_DOMINIO') ?: '';
        $tipologia = null;
        if ($idDominio && $idTipo) {
            try {
                $repo = new EntrateRepository();
                $tipologia = $repo->findDetails($idDominio, $idTipo);
            } catch (\Throwable $_) { $tipologia = null; }
        }

        return $this->twig->render($response, 'pendenze/conferma.html.twig', [
            'errors' => $errors,
            'params' => $params,
            'tipologia' => $tipologia,
            'importo' => $importoFloat,
            'voci' => $voci,
        ]);
    }

    /**
     * Mostra la form per generare le rate di una pendenza (da preview)
     */
    public function showRateizzazione(Request $request, Response $response): Response
    {
        $this->exposeCurrentUser();
        $params = (array)($request->getParsedBody() ?? []);
        // Attendi i dati della pendenza da preview
        $importo = is_numeric(str_replace(',', '.', (string)($params['importo'] ?? ''))) ? (float)str_replace(',', '.', (string)$params['importo']) : 0.0;
        $defaultRates = (int)($params['rate'] ?? 3);
        $rate = max(1, $defaultRates);
        // If the caller provided explicit parts (from preview/previous edit), prefer them
        // and preserve any empty dataScadenza values the user left blank. Only when no
        // parts are provided we generate defaults via the service.
        $parts = [];
        if (!empty($params['parts']) && is_array($params['parts'])) {
            foreach ($params['parts'] as $k => $pp) {
                $idx = is_numeric($k) ? ((int)$k) : $k;
                $parts[] = [
                    'indice' => isset($pp['indice']) ? (int)$pp['indice'] : ($idx + 1),
                    'importo' => isset($pp['importo']) ? $pp['importo'] : (isset($pp['amount']) ? $pp['amount'] : ''),
                    // preserve submitted names and empty strings (do not fill defaults)
                    'dataValidita' => $pp['dataValidita'] ?? $pp['data_validita'] ?? '',
                    'dataScadenza' => array_key_exists('dataScadenza', $pp) ? ($pp['dataScadenza'] ?? '') : ($pp['data_scadenza'] ?? ''),
                ];
            }
        } else {
            // Build default parts using service
            $parts = \App\Services\RateizzazioneService::buildPartsWithDates($importo, $rate);
        }
        return $this->twig->render($response, 'pendenze/rateizzazione.html.twig', [
            'params' => $params,
            'importo' => $importo,
            'parts' => $parts,
        ]);
    }

    /**
     * Riceve la richiesta di creare la pendenza con le rate specificate.
     * Valida la somma e poi richiama internamente create() con i parametri modificati
     */
    public function createRateizzazione(Request $request, Response $response): Response
    {
        $this->exposeCurrentUser();
        $params = (array)($request->getParsedBody() ?? []);
        // Safety guard: require an explicit confirmation flag to actually create the rates.
        // This avoids accidental creation when a POST targeting this route is triggered
        // by navigation or ambiguous submissions (e.g. from the rateizzazione 'Annulla').
        if (empty($params['confirm_create_rate'])) {
            // Delegate back to preview to show the confirmation page instead of creating.
            return $this->preview($request, $response);
        }
        $originalJson = $params['original_params'] ?? null;
        $orig = $originalJson ? json_decode($originalJson, true) : [];
        // Merge explicit posted scalar values over decoded original (safety)
        foreach ($params as $k => $v) {
            // Evitiamo di propagare parametri della view (es. 'return') nei
            // payload inviati al Backoffice. Skip espliciti per i parametri
            // usati solo dalla UI.
            if (in_array($k, ['original_params', 'parts', 'return', 'submit', 'csrf_token'], true)) continue;
            if (is_array($v)) continue; // parts or nested arrays handled elsewhere
            $orig[$k] = $v;
        }
        $parts = $params['parts'] ?? [];
        // Normalizza importi
        $sumCents = 0;
        foreach ($parts as $p) {
            $raw = (string)($p['importo'] ?? '');
            $v = is_numeric(str_replace(',', '.', $raw)) ? (float)str_replace(',', '.', $raw) : 0.0;
            $sumCents += (int)round($v * 100);
        }
        $originalTotalRaw = (string)($orig['importo'] ?? '');
        $originalTotal = is_numeric(str_replace(',', '.', $originalTotalRaw)) ? (float)str_replace(',', '.', $originalTotalRaw) : 0.0;
        $totalCents = (int)round($originalTotal * 100);
        if ($sumCents !== $totalCents) {
            // Ritorna al form di rateizzazione con messaggio d'errore e dati ricostruiti
            $error = 'La somma delle rate (' . number_format($sumCents/100, 2, ',', '.') . ') non corrisponde esattamente all\'importo totale (' . number_format($totalCents/100, 2, ',', '.') . ').';
            return $this->twig->render($response, 'pendenze/rateizzazione.html.twig', [
                'params' => $orig,
                'importo' => $totalCents/100,
                'parts' => $parts,
                'errors' => [$error],
            ]);
        }
        // Costruisci il documento di rateizzazione e non sostituire le voci originali.
        // Manteniamo le voci originali (la pendenza rimane concettualmente la stessa)
        $doc = is_array($orig['documento'] ?? null) ? $orig['documento'] : [];
        // assicurati identificativo/descrizione documento
        if (empty($doc['identificativo'])) {
            try {
                $doc['identificativo'] = 'RATA-' . substr(bin2hex(random_bytes(6)), 0, 12);
            } catch (\Throwable $_) {
                $doc['identificativo'] = 'RATA-' . uniqid();
            }
        }
        // Imposta la descrizione del documento sulla causale originale (dal form precedente),
        // aggiungendo il numero di rate tra parentesi.
        $originalCausale = $orig['causale'] ?? 'Pagamento rateizzato';
        $numRate = count($parts);
        $doc['descrizione'] = trim((string)$originalCausale) . " ({$numRate} rate)";

        $doc['rata'] = [];
        foreach ($parts as $idx => $p) {
            $doc['rata'][] = [
                'indice' => isset($p['indice']) ? (int)$p['indice'] : ($idx + 1),
                'importo' => is_numeric(str_replace(',', '.', (string)($p['importo'] ?? ''))) ? (float)str_replace(',', '.', (string)$p['importo']) : 0.0,
                'dataValidita' => $p['dataValidita'] ?? ($p['data_validita'] ?? null),
                'dataScadenza' => $p['dataScadenza'] ?? ($p['data_scadenza'] ?? null),
            ];
        }

        // Merge: mantieni le voci originali e aggiungi il documento + metadata di rate
        $merged = $orig;
        $merged['documento'] = $doc;
        $merged['proprieta'] = is_array($merged['proprieta'] ?? null) ? $merged['proprieta'] : [];
        $merged['proprieta']['numeroRate'] = count($parts);
        $merged['proprieta']['rate'] = $doc['rata'];

        // Genera un idPendenza deterministico (client-side) se non fornito, in modo
        // che il redirect al dettaglio sia prevedibile. Rispetta il pattern
        // (^ [A-Za-z0-9\-_]{1,35} $)
        $idPendenzaRaw = trim((string)($merged['idPendenza'] ?? ''));
        if ($idPendenzaRaw === '') {
            try {
                $rand = bin2hex(random_bytes(8));
            } catch (\Throwable $_) {
                $rand = preg_replace('/[^A-Za-z0-9]/', '', uniqid());
            }
            $idPendenzaCand = 'GIL-' . substr($rand, 0, 16);
        } else {
            $idPendenzaCand = $idPendenzaRaw;
        }
        $idPendenzaSanitized = preg_replace('/[^A-Za-z0-9\-_]/', '-', $idPendenzaCand);
        $idPendenzaSanitized = substr($idPendenzaSanitized, 0, 35);
        $merged['idPendenza'] = $idPendenzaSanitized;

    // Inviare ogni rata come singola pendenza attraverso l'API Pendenze (PUT).
    // Creiamo una pendenza per ogni rata e chiamiamo internamente
    // sendPendenzaToBackoffice(). Se tutte le invii hanno successo, redirect
    // alla ricerca; altrimenti mostriamo gli errori.
    $created = [];
    $responses = [];
    $allErrors = [];
    $baseId = $merged['idPendenza'] ?? '';
    $totalParts = count($parts); // Numero totale delle rate
    foreach ($parts as $idx => $p) {
        $rIndex = isset($p['indice']) ? (int)$p['indice'] : ($idx + 1);
        $single = $merged;
        // imposta importo e date specifiche della rata
        $single['importo'] = is_numeric(str_replace(',', '.', (string)($p['importo'] ?? ''))) ? (float)str_replace(',', '.', (string)$p['importo']) : 0.0;
        if (!empty($p['dataValidita'])) $single['dataValidita'] = $p['dataValidita'];
        if (!empty($p['dataScadenza'])) $single['dataScadenza'] = $p['dataScadenza'];
        $docSingle = is_array($single['documento'] ?? null) ? $single['documento'] : [];
        $docSingle['rata'] = $rIndex;

        // *** INIZIO MODIFICA DESCRIZIONE SINGOLA RATA ***
        // Recupera la causale originale (che è la "Descrizione originale" dal form)
        $originalCausale = $orig['causale'] ?? 'Pagamento rateizzato';
        // Assicurati che rIndex sia un intero valido
        if (!is_int($rIndex) || $rIndex < 1) {
            $rIndex = $idx + 1;
        }
        // Assicurati che totalParts sia un intero valido
        if (!is_int($totalParts) || $totalParts < 1) {
            $totalParts = count($parts);
        }
        // Imposta la causale della singola rata
        $single['causale'] = trim((string)$originalCausale) . " (Rata {$rIndex} di {$totalParts})";
        // Imposta anche la descrizione del documento della singola rata (per completezza)
        $docSingle['descrizione'] = trim((string)$originalCausale) . " (Rate {$totalParts})";
        // *** FINE MODIFICA DESCRIZIONE SINGOLA RATA ***

        $single['documento'] = $docSingle;
        // idPendenza per la rata
        $idPForRate = ($baseId !== '' ? $baseId : (function() {
            try { return 'GIL-' . substr(bin2hex(random_bytes(8)), 0, 16); } catch (\Throwable $_) { return 'GIL-' . uniqid(); }
        })()) . '-R' . $rIndex;
        $single['idPendenza'] = preg_replace('/[^A-Za-z0-9\-_]/', '-', substr((string)$idPForRate, 0, 35));

        // DB-first: normalize voci (try to enrich from DB and validate required accounting fields)
        $localVociErrors = [];
        $idDominioUsed = $single['idDominio'] ?? (getenv('ID_DOMINIO') ?: '');
        // Preserve the original voci as basis for proportional allocation
        $originalVociBasis = is_array($single['voci'] ?? null) ? $single['voci'] : [];
        $single['voci'] = $this->buildVociForInsertionFromMerged($single, $single['voci'] ?? [], $idDominioUsed, $localVociErrors);

        // Ensure the sum of voce.importo equals the rata importo by allocating
        // amounts proportionally (in cents) based on the original voci basis.
        $rateTotal = is_numeric(str_replace(',', '.', (string)($single['importo'] ?? 0))) ? (float)str_replace(',', '.', (string)$single['importo']) : 0.0;
        $rateCents = (int)round($rateTotal * 100);
        $allocatedCents = [];
        $numBuilt = count($single['voci']);
        $origSum = 0.0;
        foreach ($originalVociBasis as $ov) {
            $origSum += is_numeric(str_replace(',', '.', (string)($ov['importo'] ?? 0))) ? (float)str_replace(',', '.', (string)$ov['importo']) : 0.0;
        }
        // Choose an allocation basis: original basis if counts match and sum > 0,
        // otherwise fall back to built voci amounts; if still zero, equal split.
        $basis = $originalVociBasis;
        $basisSum = $origSum;
        if ($basisSum <= 0.0 || count($basis) !== $numBuilt) {
            $basis = $single['voci'];
            $basisSum = 0.0;
            foreach ($basis as $b) {
                $basisSum += is_numeric(str_replace(',', '.', (string)($b['importo'] ?? 0))) ? (float)str_replace(',', '.', (string)$b['importo']) : 0.0;
            }
        }

        if ($numBuilt === 0) {
            $localVociErrors[] = 'Nessuna voce definita per la rata.';
        } else {
            if ($basisSum > 0.0) {
                // proportional allocation
                for ($i = 0; $i < $numBuilt; $i++) {
                    $share = is_numeric(str_replace(',', '.', (string)($basis[$i]['importo'] ?? 0))) ? (float)str_replace(',', '.', (string)$basis[$i]['importo']) : 0.0;
                    $allocatedCents[$i] = (int)round(($share / $basisSum) * $rateCents);
                }
            } else {
                // equal split
                $base = intdiv($rateCents, $numBuilt);
                $rem = $rateCents - ($base * $numBuilt);
                for ($i = 0; $i < $numBuilt; $i++) {
                    $allocatedCents[$i] = $base + ($i < $rem ? 1 : 0);
                }
            }

            // Fix rounding differences by distributing the diff across entries
            $sumAllocated = array_sum($allocatedCents);
            $diff = $rateCents - $sumAllocated;
            if ($diff !== 0) {
                $step = $diff > 0 ? 1 : -1;
                $remain = abs($diff);
                for ($k = 0; $k < $remain; $k++) {
                    $idx = $k % $numBuilt;
                    $allocatedCents[$idx] += $step;
                }
            }

            // Apply allocated amounts back to built voci
            for ($i = 0; $i < $numBuilt; $i++) {
                $single['voci'][$i]['importo'] = ($allocatedCents[$i] ?? 0) / 100.0;

                // *** INIZIO MODIFICA RICHIESTA ***
                // Modifica la descrizione della voce per includere il numero rata
                $originalDesc = $single['voci'][$i]['descrizione'] ?? $merged['causale'] ?? 'Pagamento';
                // Rimuovi eventuale "Rata X di Y" precedente se la descrizione la conteneva già
                $originalDesc = preg_replace('/\s*\(Rata \d+ di \d+\)$/', '', $originalDesc);
                $single['voci'][$i]['descrizione'] = trim($originalDesc) . " (Rata {$rIndex} di {$totalParts})";
                // *** FINE MODIFICA RICHIESTA ***
            }

            if ((getenv('APP_DEBUG') !== false) && getenv('APP_DEBUG')) {
                Logger::getInstance()->debug('Allocated voce importi per rata', [
                    'idPendenza' => $idPForRate,
                    'rata_importo' => $rateTotal,
                    'basis_sum' => $basisSum,
                    'allocated' => array_map(fn($c) => $c / 100.0, $allocatedCents),
                ]);
            }
        }
        if (!empty($localVociErrors)) {
            foreach ($localVociErrors as $le) $allErrors[] = "Rata {$rIndex}: " . $le;
            // skip sending this rata
            continue;
        }

        // Sanitize top-level keys to avoid sending UI-only parameters (es. 'return')
        $allowed = [
            'idTipoPendenza','idDominio','causale','importo','annoRiferimento',
            'soggettoPagatore','voci','documento','proprieta','allegati',
            'dataValidita','dataScadenza','direzione','divisione','cartellaPagamento',
            'numeroAvviso','tassonomia','dataPromemoriaScadenza','idUnitaOperativa',
            'dataCaricamento','datiAllegati','tassonomiaAvviso','dataNotificaAvviso',
            'nome'
        ];
    // Normalizza il soggettoPagatore: unisci nome+anagrafica e rimuovi 'nome'
        if (isset($single['soggettoPagatore']) && is_array($single['soggettoPagatore'])) {
            $s = $single['soggettoPagatore'];
            $tipo = strtoupper((string)($s['tipo'] ?? 'F'));
            $anag = trim((string)($s['anagrafica'] ?? ''));
            $nome = trim((string)($s['nome'] ?? ''));
            if ($tipo === 'F') {
                $full = trim(($nome !== '' ? $nome . ' ' : '') . $anag);
                $s['anagrafica'] = $full !== '' ? $full : $anag;
            } else {
                $s['anagrafica'] = $anag;
            }
            // Email: keep only if scalar and valid
            if (array_key_exists('email', $s)) {
                $raw = $s['email'];
                if (!is_scalar($raw) || trim((string)$raw) === '' || filter_var(trim((string)$raw), FILTER_VALIDATE_EMAIL) === false) {
                    unset($s['email']);
                } else {
                    $s['email'] = trim((string)$raw);
                }
            }
            // Cellulare: keep only scalar non-empty
            if (array_key_exists('cellulare', $s)) {
                $raw = $s['cellulare'];
                if (!is_scalar($raw) || trim((string)$raw) === '') {
                    unset($s['cellulare']);
                } else {
                    $s['cellulare'] = trim((string)$raw);
                }
            }
            if (isset($s['nome'])) unset($s['nome']);
            $single['soggettoPagatore'] = $s;
        }

        // Rimuoviamo campi che possono essere passati come array vuoto dalla UI
        // e che il Backoffice si aspetta come stringhe (es. cartellaPagamento).
        $stringFields = ['cartellaPagamento', 'direzione', 'divisione'];
        foreach ($stringFields as $sf) {
            if (!array_key_exists($sf, $single)) continue;
            $val = $single[$sf];
            if (!is_scalar($val) || trim((string)$val) === '') {
                unset($single[$sf]);
            } else {
                $single[$sf] = trim((string)$val);
            }
        }

        // Sanitize 'proprieta' per evitare di inviare campi non supportati
        if (isset($single['proprieta']) && is_array($single['proprieta'])) {
            $allowedPropKeys = [
                'descrizioneImporto', 'lineaTestoRicevuta1', 'lineaTestoRicevuta2',
                'linguaSecondaria', 'linguaSecondariaCausale'
            ];
            $single['proprieta'] = array_intersect_key($single['proprieta'], array_flip($allowedPropKeys));
            if (empty($single['proprieta'])) {
                unset($single['proprieta']);
            }
        }

        $single = array_intersect_key($single, array_flip($allowed));

        // Rimuoviamo idPendenza dal body (deve essere passato solo nell'URL)
        $idForUrl = $idPForRate;
        if (isset($single['idPendenza'])) unset($single['idPendenza']);

        // Invia singola pendenza
        $res = $this->sendPendenzaToBackoffice($single, $idForUrl);
        $responses[] = $res;
        if (!empty($res) && !empty($res['success'])) {
            $created[] = $res['idPendenza'] ?? $idForUrl;
        } else {
            $errs = $res['errors'] ?? ['Errore invio pendenza rata ' . $rIndex];
            foreach ((array)$errs as $e) $allErrors[] = "Rata {$rIndex}: " . (string)$e;
        }
    }

    // moved buildVociForInsertionFromMerged to class scope (below)

    if (empty($allErrors)) {
        // Tutte le rate inviate con successo: costruiamo un documento multirata
        // da poter stampare. Salviamo in sessione il documento sintetico con le
        // risposte per ogni rata (ad uso stampa/preview).
        $multi = [
            'documento' => $merged['documento'] ?? null,
            'proprieta' => $merged['proprieta'] ?? null,
            'soggettoPagatore' => $merged['soggettoPagatore'] ?? null,
            'voci' => $merged['voci'] ?? null,
            'idDominio' => $merged['idDominio'] ?? (getenv('ID_DOMINIO') ?: ''),
            'rates' => [],
            'responses' => $responses,
        ];
        foreach ($parts as $i => $p) {
            $rateId = $created[$i] ?? null;
            $rateResp = $controllerRate = null; // placeholder
            // sendPendenzaToBackoffice returns 'response' when available; try to get it
            // from past call results: in our loop above we used $res variable but
            // did not persist it — modify loop to persist responses if needed.
        }
        // Forniamo un semplice array con gli id creati e i metadati della rata
        foreach ($created as $i => $cid) {
            $r = $parts[$i] ?? [];
            $resp = $responses[$i]['response'] ?? null;
            $numeroAvviso = null;
            if (is_array($resp)) {
                if (!empty($resp['numeroAvviso'])) $numeroAvviso = $resp['numeroAvviso'];
                elseif (!empty($resp['numero_avviso'])) $numeroAvviso = $resp['numero_avviso'];
                elseif (!empty($resp['pendenza']['numeroAvviso'])) $numeroAvviso = $resp['pendenza']['numeroAvviso'];
                elseif (!empty($resp['pendenza']['numero_avviso'])) $numeroAvviso = $resp['pendenza']['numero_avviso'];
                elseif (!empty($resp['avvisi'][0]['numeroAvviso'])) $numeroAvviso = $resp['avvisi'][0]['numeroAvviso'];
            }
            $multi['rates'][] = [
                'idPendenza' => $cid,
                'indice' => $r['indice'] ?? ($i + 1),
                'importo' => $r['importo'] ?? null,
                'dataValidita' => $r['dataValidita'] ?? null,
                'dataScadenza' => $r['dataScadenza'] ?? null,
                'numeroAvviso' => $numeroAvviso,
                'backoffice_response' => $resp,
            ];
        }
        // Memorizza il documento sintetico in sessione per il rendering/stampa
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        $_SESSION['multi_rate_document'] = $multi;

        if (!empty($created)) {
            foreach ($created as $c) {
                $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Pendenza creata: ' . $c];
            }
            // Redirect to the dettaglio of the first created rata (behaviour like insertion)
            $firstId = $created[0];
            $loc = '/pendenze/dettaglio/' . rawurlencode((string)$firstId) . '?from=insert';
            return $response->withHeader('Location', $loc)->withStatus(302);
        } else {
            $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Rate create con successo'];
            // Fallback: preview multi-rate document
            $loc = '/pendenze/multirata/preview';
            if (!empty($created[0])) $loc .= '?id=' . rawurlencode($created[0]);
            return $response->withHeader('Location', $loc)->withStatus(302);
        }
    }

    // Se siamo qui, almeno una rata ha fallito: ritorniamo al form con errori
    return $this->twig->render($response, 'pendenze/rateizzazione.html.twig', [
        'params' => $orig,
        'importo' => $originalTotal,
        'parts' => $parts,
        'errors' => $allErrors,
    ]);
    }

    /**
     * Helper: invia singola pendenza al backoffice. Ritorna array con chiavi
     * 'success' (bool), 'idPendenza' e 'errors' (array)
     */
    private function sendPendenzaToBackoffice(array $payload, ?string $idPendenza = null): array
    {
        $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
        $idA2A = getenv('ID_A2A') ?: '';
        $errors = [];
        if (empty($backofficeUrl)) {
            $errors[] = 'GOVPAY_BACKOFFICE_URL non impostata';
            return ['success' => false, 'errors' => $errors];
        }
        if ($idA2A === '') {
            $errors[] = 'ID_A2A non impostata';
            return ['success' => false, 'errors' => $errors];
        }

        $guzzleOptions = [];
        $authMethod = getenv('AUTHENTICATION_GOVPAY');
        if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
            $cert = getenv('GOVPAY_TLS_CERT');
            $key = getenv('GOVPAY_TLS_KEY');
            $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
            if (!empty($cert) && !empty($key)) {
                $guzzleOptions['cert'] = $cert;
                $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
            } else {
                $errors[] = 'mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati';
                return ['success' => false, 'errors' => $errors];
            }
        }

        try {
            $httpClient = new Client($guzzleOptions);
            // Genera idPendenza se non fornito
            $idP = trim((string)($idPendenza ?? ($payload['idPendenza'] ?? '')));
            if ($idP === '') {
                try {
                    $rand = bin2hex(random_bytes(8));
                } catch (\Throwable $_) {
                    $rand = preg_replace('/[^A-Za-z0-9]/', '', uniqid());
                }
                $idPCand = 'GIL-' . substr($rand, 0, 16);
                $idP = preg_replace('/[^A-Za-z0-9\-_]/', '-', substr($idPCand, 0, 35));
            }

            // Ensure idPendenza is not sent in the request body: the API expects it in the URL
            if (array_key_exists('idPendenza', $payload)) {
                if ((getenv('APP_DEBUG') !== false) && getenv('APP_DEBUG')) {
                    Logger::getInstance()->debug('Removed idPendenza from request body (sent in URL)', ['idPendenza' => $payload['idPendenza']]);
                }
                unset($payload['idPendenza']);
            }
            // Defensive sanitization: filter proprieta to only allowed Backoffice fields
            if (isset($payload['proprieta']) && is_array($payload['proprieta'])) {
                $allowedPropKeys = [
                    'descrizioneImporto', 'lineaTestoRicevuta1', 'lineaTestoRicevuta2',
                    'linguaSecondaria', 'linguaSecondariaCausale'
                ];
                $payload['proprieta'] = array_intersect_key($payload['proprieta'], array_flip($allowedPropKeys));
                if (empty($payload['proprieta'])) {
                    unset($payload['proprieta']);
                }
                if ((getenv('APP_DEBUG') !== false) && getenv('APP_DEBUG')) {
                    Logger::getInstance()->debug('Sanitized proprieta before sending pendenza', ['proprieta' => $payload['proprieta'] ?? null]);
                }
            }
            // Remove empty string-like fields that the Backoffice enforces as non-empty
            foreach (['cartellaPagamento', 'direzione', 'divisione'] as $sf) {
                if (isset($payload[$sf]) && (!is_scalar($payload[$sf]) || trim((string)$payload[$sf]) === '')) {
                    if ((getenv('APP_DEBUG') !== false) && getenv('APP_DEBUG')) {
                        Logger::getInstance()->debug('Removed empty string-like field before send', ['field' => $sf]);
                    }
                    unset($payload[$sf]);
                }
            }

            $url = rtrim($backofficeUrl, '/') . '/pendenze/' . rawurlencode($idA2A) . '/' . rawurlencode($idP);
            $username = getenv('GOVPAY_USER');
            $password = getenv('GOVPAY_PASSWORD');
            $requestOptions = [
                'headers' => ['Accept' => 'application/json'],
                'json' => $payload,
            ];
            if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                $requestOptions['auth'] = [$username, $password];
            }

            if ((getenv('APP_DEBUG') !== false) && getenv('APP_DEBUG')) {
                Logger::getInstance()->debug('Pendenze PUT ' . $url, ['payload' => $payload]);
            }

            $resp = $httpClient->request('PUT', $url, $requestOptions);
            $code = $resp->getStatusCode();
            $body = (string)$resp->getBody();
            $data = json_decode($body, true);
            return ['success' => $code >= 200 && $code < 300, 'idPendenza' => $idP, 'errors' => [], 'response' => $data];
        } catch (ClientException $ce) {
            $detail = '';
            $resp = $ce->getResponse();
            if ($resp) {
                try {
                    $stream = $resp->getBody();
                    if (is_callable([$stream, 'rewind'])) {
                        try { $stream->rewind(); } catch (\Throwable $_) { }
                    }
                    if (is_callable([$stream, 'getContents'])) {
                        $detail = $stream->getContents();
                    } else {
                        $detail = (string)$stream;
                    }
                } catch (\Throwable $_) {
                    try { $detail = (string)$resp->getBody(); } catch (\Throwable $_) { $detail = ''; }
                }
            }
            $errors[] = $detail ?: $ce->getMessage();
            // Non esponiamo il raw_response alla UI; i log Guzzle con APP_DEBUG
            // contengono l'intera transazione per i debug necessari.
            $parsed = null;
            if ($detail !== '') {
                $tmp = json_decode($detail, true);
                $parsed = (json_last_error() === JSON_ERROR_NONE) ? $tmp : $detail;
            }
            return ['success' => false, 'errors' => $errors, 'response' => $parsed];
        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();
            return ['success' => false, 'errors' => $errors];
        }
    }

    /**
     * Costruisce le voci per l'inserimento applicando la logica DB-first.
     * Restituisce le voci normalizzate e popola $errors se ci sono problemi.
     */
    private function buildVociForInsertionFromMerged(array $merged, array $voci, string $idDominio, array &$errors = []): array
    {
        $out = [];
        $repo = null;
        try { $repo = new EntrateRepository(); } catch (\Throwable $_) { $repo = null; }
        foreach ($voci as $v) {
            $vv = $v;
            if (isset($vv['importo'])) {
                $vv['importo'] = is_numeric(str_replace(',', '.', (string)$vv['importo'])) ? (float)str_replace(',', '.', (string)$vv['importo']) : 0.0;
            } else {
                $vv['importo'] = 0.0;
            }

            $vv['idVocePendenza'] = trim((string)($vv['idVocePendenza'] ?? ($vv['id_voce_pendenza'] ?? '')));

            // DB-first: try to get defaults
            $details = null;
            if ($repo !== null && $idDominio !== '' && !empty($merged['idTipoPendenza'] ?? null)) {
                try { $details = $repo->findDetails($idDominio, (string)$merged['idTipoPendenza']); } catch (\Throwable $_) { $details = null; }
            }
            $finalIban = '';
            $finalCodEntrata = '';
            $finalTipoBollo = '';
            $finalTipoContabilita = '';
            if ($details) {
                if (!empty($details['iban_accredito'])) $finalIban = $details['iban_accredito'];
                if (!empty($details['codice_contabilita'])) $finalCodEntrata = $details['codice_contabilita'];
                if (!empty($details['tipo_bollo'])) $finalTipoBollo = $details['tipo_bollo'];
                if (!empty($details['tipo_contabilita'])) $finalTipoContabilita = $details['tipo_contabilita'];
            }
            if (empty($finalIban) && !empty($merged['ibanAccredito'])) $finalIban = $merged['ibanAccredito'];
            if (empty($finalCodEntrata) && (!empty($merged['codiceContabilita']) || !empty($merged['codEntrata']))) $finalCodEntrata = $merged['codiceContabilita'] ?? $merged['codEntrata'] ?? '';
            if ($merged['idTipoPendenza'] ?? '') {
                $finalCodEntrata = (string)$merged['idTipoPendenza'];
            }
            if (empty($finalTipoBollo) && !empty($merged['tipoBollo'])) $finalTipoBollo = $merged['tipoBollo'];
            if (empty($finalTipoContabilita) && !empty($merged['tipoContabilita'])) $finalTipoContabilita = $merged['tipoContabilita'];

            $voiceMode = null;
            if ($finalTipoBollo !== '') {
                $voiceMode = 'bollo';
            } else {
                $hasEntrata = ($finalIban !== '' && $finalTipoContabilita !== '' && $finalCodEntrata !== '');
                if ($hasEntrata) $voiceMode = 'entrata';
                elseif ($finalCodEntrata !== '') $voiceMode = 'riferimento';
                else $voiceMode = null;
            }
            if ($voiceMode === 'bollo') {
                $vv['tipoBollo'] = $finalTipoBollo;
            } elseif ($voiceMode === 'entrata') {
                $vv['ibanAccredito'] = $finalIban;
                $vv['tipoContabilita'] = $finalTipoContabilita;
                $vv['codiceContabilita'] = $finalCodEntrata;
            } elseif ($voiceMode === 'riferimento') {
                $vv['codEntrata'] = $finalCodEntrata;
            } else {
                $errors[] = "Voce '{$vv['idVocePendenza']}' priva di informazioni di contabilita' (IBAN+tipoContabilita+codiceContabilita o codEntrata o tipoBollo).";
            }
            $out[] = $vv;
        }
        return $out;
    }

    /**
     * Recupera i dettagli di una pendenza dal Backoffice per ottenere campi come numeroAvviso
     */
    // (no longer needed) fetchPendenzaDetailFromBackoffice removed

    /**
     * Crea e invia un tracciato con le pendenze delle rate al Backoffice
     * Restituisce ['success'=>bool, 'idTracciato'=>string|null, 'errors'=>[]]
     */
    private function sendTracciatoToBackoffice(array $merged, array $parts): array
    {
        $svc = $this->tracciatoService ?? new TracciatoService();
        return $svc->sendTracciato($merged, $parts);
    }

    /**
     * Crea un Guzzle client con opzioni di autenticazione e mTLS impostate
     */
    protected function makeHttpClient(array $guzzleOptions = []): Client
    {
        $authMethod = getenv('AUTHENTICATION_GOVPAY');
        if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
            $cert = getenv('GOVPAY_TLS_CERT');
            $key = getenv('GOVPAY_TLS_KEY');
            $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
            if (!empty($cert) && !empty($key)) {
                $guzzleOptions['cert'] = $cert;
                $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
            }
        }

        // Crea un handler stack completamente pulito senza alcun middleware
        $handlerStack = new \GuzzleHttp\HandlerStack();
        $handlerStack->setHandler(new \GuzzleHttp\Handler\CurlHandler());

        $guzzleOptions['handler'] = $handlerStack;

        return new Client($guzzleOptions);
    }

    /**
     * Helper: call Backoffice /pendenze endpoint with given query parameters
     * Returns decoded response array or throws on error.
     *
     * @param array $query
     * @return array
     * @throws \Throwable
     */
    private function callBackofficeFindPendenze(array $query): array
    {
        $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
        if (empty($backofficeUrl)) {
            throw new \RuntimeException('Variabile GOVPAY_BACKOFFICE_URL non impostata');
        }

        $username = getenv('GOVPAY_USER');
        $password = getenv('GOVPAY_PASSWORD');

        $http = $this->makeHttpClient();
        $requestOptions = [
            'headers' => ['Accept' => 'application/json'],
            'query' => $query,
        ];
        if ($username !== false && $password !== false && $username !== '' && $password !== '') {
            $requestOptions['auth'] = [$username, $password];
        }

        $url = rtrim($backofficeUrl, '/') . '/pendenze';
        $resp = $http->request('GET', $url, $requestOptions);
        $json = (string)$resp->getBody();
        $dataArr = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($dataArr)) {
            throw new \RuntimeException('Parsing JSON fallito: ' . json_last_error_msg());
        }
        return $dataArr;
    }

    public function showDetail(Request $request, Response $response, array $args): Response
    {
        $this->exposeCurrentUser();

        $idPendenza = $args['idPendenza'] ?? '';
    $q = $request->getQueryParams();
    $ret = $q['return'] ?? '/pendenze/ricerca';
    $cameFromInsert = isset($q['from']) && $q['from'] === 'insert';
        if (strpos((string)$ret, '/pendenze/ricerca') !== 0) {
            $ret = '/pendenze/ricerca';
        }

        $error = null;
        $pendenza = null;

        $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
        $idA2A = getenv('ID_A2A') ?: '';
        if ($idPendenza === '') {
            $error = 'ID pendenza non specificato';
        } elseif (empty($backofficeUrl)) {
            $error = 'Variabile GOVPAY_BACKOFFICE_URL non impostata';
        } elseif ($idA2A === '') {
            $error = 'Variabile ID_A2A non impostata nel file .env';
        } else {
            try {
                $username = getenv('GOVPAY_USER');
                $password = getenv('GOVPAY_PASSWORD');
                $guzzleOptions = [
                    'headers' => ['Accept' => 'application/json'],
                ];
                $authMethod = getenv('AUTHENTICATION_GOVPAY');
                if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
                    $cert = getenv('GOVPAY_TLS_CERT');
                    $key = getenv('GOVPAY_TLS_KEY');
                    $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
                    if (!empty($cert) && !empty($key)) {
                        $guzzleOptions['cert'] = $cert;
                        $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
                    } else {
                        $error = 'mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati';
                    }
                }
                if (!$error && $username !== false && $password !== false && $username !== '' && $password !== '') {
                    $guzzleOptions['auth'] = [$username, $password];
                }

                if (!$error) {
                    $http = $this->makeHttpClient($guzzleOptions);
                    $url = rtrim($backofficeUrl, '/') . '/pendenze/' . rawurlencode((string)$idA2A) . '/' . rawurlencode($idPendenza);
                    $resp = $http->request('GET', $url);
                    $json = (string)$resp->getBody();
                    $data = json_decode($json, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \RuntimeException('Parsing JSON fallito: ' . json_last_error_msg());
                    }
                    $pendenza = $data;
                }
            } catch (ClientException $ce) {
                $code = $ce->getResponse() ? $ce->getResponse()->getStatusCode() : 0;
                $error = $code === 404 ? 'Pendenza non trovata (404)' : 'Errore client nella chiamata pendenza: ' . $ce->getMessage();
            } catch (\Throwable $e) {
                $error = 'Errore chiamata pendenza: ' . $e->getMessage();
            }
        }

        // Attempt to fetch related pendenze for the same document by searching
        // for pendenze with the same debtor (idDebitore) and the same
        // dataCaricamento day, then filter locally by documento.identificativo.
        $relatedPendenze = [];
        try {
            if (is_array($pendenza) && !empty($pendenza)) {
                $documentId = $pendenza['documento']['identificativo'] ?? $pendenza['documento']['identificativoDocumento'] ?? null;
                // Deriva idDebitore dal soggettoPagatore.identificativo o da idDebitore diretto
                $idDebitore = $pendenza['soggettoPagatore']['identificativo'] ?? $pendenza['idDebitore'] ?? null;
                $dataCar = $pendenza['dataCaricamento'] ?? $pendenza['data_caricamento'] ?? null;
                $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
                $idA2A = getenv('ID_A2A') ?: '';
                if ($documentId && $idDebitore && $dataCar && $backofficeUrl && $idA2A) {
                    // build HTTP client options (reuse existing approach)
                    $username = getenv('GOVPAY_USER');
                    $password = getenv('GOVPAY_PASSWORD');
                    $guzzleOptions = ['headers' => ['Accept' => 'application/json']];
                    $authMethod = getenv('AUTHENTICATION_GOVPAY');
                    if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
                        $cert = getenv('GOVPAY_TLS_CERT');
                        $key = getenv('GOVPAY_TLS_KEY');
                        $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
                        if (!empty($cert) && !empty($key)) {
                            $guzzleOptions['cert'] = $cert;
                            $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
                        }
                    }
                    if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                        $guzzleOptions['auth'] = [$username, $password];
                    }
                    $http = $this->makeHttpClient($guzzleOptions);

                    // Date range for the same generation day
                    try {
                        $dt = new \DateTime($dataCar);
                        $start = $dt->format('Y-m-d') . 'T00:00:00';
                        $end = $dt->format('Y-m-d') . 'T23:59:59';
                    } catch (\Throwable $_) {
                        $start = null;
                        $end = null;
                    }

                    if ($start !== null && $end !== null) {
                        $page = 1;
                        $perPage = 100;
                        $maxPages = 50; // safety cap to avoid infinite loops
                        while ($page > 0 && $page <= $maxPages) {
                            $query = [
                                'idDominio' => $pendenza['idDominio'] ?? (getenv('ID_DOMINIO') ?: ''),
                                'idA2A' => $idA2A,
                                'idDebitore' => $idDebitore,
                                'dataDa' => $start,
                                'dataA' => $end,
                                'pagina' => $page,
                                'risultatiPerPagina' => $perPage,
                            ];
                            try {
                                $dataArr = $this->callBackofficeFindPendenze($query);
                                $candidates = $dataArr['risultati'] ?? $dataArr['results'] ?? $dataArr;
                                if (is_array($candidates)) {
                                    foreach ($candidates as $cand) {
                                        $candDocId = $cand['documento']['identificativo'] ?? $cand['documento']['identificativoDocumento'] ?? null;
                                        if ($candDocId !== null && (string)$candDocId === (string)$documentId) {
                                            $relatedPendenze[] = $cand;
                                        }
                                    }
                                }
                                // paging heuristics: try to detect last page
                                $numPagine = $dataArr['numPagine'] ?? $dataArr['num_pagine'] ?? ($dataArr['metaDatiPaginazione']['numPagine'] ?? null);
                                if ($numPagine !== null) {
                                    $numPagine = (int)$numPagine;
                                    if ($page >= $numPagine) break;
                                } else {
                                    // if fewer results than page size, likely last page
                                    if (!is_array($candidates) || count($candidates) < $perPage) break;
                                }
                                $page++;
                            } catch (\Throwable $e) {
                                // Log and break: don't fail the whole detail page
                                Logger::getInstance()->warning('Errore ricerca pendenze correlate: ' . $e->getMessage(), ['idDebitore' => $idDebitore, 'documento' => $documentId]);
                                break;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $_) {
            // swallow: non vogliamo bloccare la visualizzazione del dettaglio
            Logger::getInstance()->warning('Errore building related pendenze: ' . $_->getMessage());
            $relatedPendenze = [];
        }

        // Build a quick lookup of related pendenze keyed by their rata indice (if available)
        $relatedByRata = [];
        if (is_array($relatedPendenze) && count($relatedPendenze) > 0) {
            foreach ($relatedPendenze as $rp) {
                $indices = [];
                if (isset($rp['documento']['rata'])) {
                    if (!is_array($rp['documento']['rata'])) {
                        $indices[] = (string)$rp['documento']['rata'];
                    } else {
                        foreach ($rp['documento']['rata'] as $rra) {
                            if (isset($rra['indice'])) $indices[] = (string)$rra['indice'];
                        }
                    }
                }
                if (isset($rp['proprieta']['rate']) && is_array($rp['proprieta']['rate'])) {
                    foreach ($rp['proprieta']['rate'] as $rra) {
                        if (isset($rra['indice'])) $indices[] = (string)$rra['indice'];
                    }
                }
                $indices = array_filter(array_unique($indices), fn($v) => $v !== '');
                foreach ($indices as $i) {
                    $relatedByRata[(string)$i] = $rp;
                }
            }
        }

        // Determine explicitly whether the opened pendenza contains a scalar
        // documento.rata (the only acceptable source for automatic highlighting).
        $currentRate = null;
        $rateInfoSource = 'none';
        if (is_array($pendenza) && !empty($pendenza)) {
            if (isset($pendenza['documento']['rata']) && !is_array($pendenza['documento']['rata'])) {
                $currentRate = (string)$pendenza['documento']['rata'];
                $rateInfoSource = 'documento_rata_scalar';
            } elseif (isset($pendenza['documento']['rata']) && is_array($pendenza['documento']['rata'])) {
                $rateInfoSource = 'documento_rata_array';
            } elseif (isset($pendenza['proprieta']['rate']) && is_array($pendenza['proprieta']['rate'])) {
                $rateInfoSource = 'proprieta_rate';
            } elseif (isset($pendenza['proprieta']['numeroRate'])) {
                $rateInfoSource = 'proprieta_numeroRate';
            }

            // IMPORTANT: do not attempt to infer the current rate from related
            // pendenze. This was a heuristic that produced non-deterministic
            // highlights and hid the real issue (missing scalar value in the
            // primary pendenza). Log (when in debug) so we can diagnose.
            if ($currentRate === null && ((getenv('APP_DEBUG') !== false) && getenv('APP_DEBUG'))) {
                Logger::getInstance()->info('No scalar documento.rata found on pendenza; automatic highlighting disabled', ['idPendenza' => $idPendenza, 'rate_info_source' => $rateInfoSource]);
            }
        }

        // Fetch tipologie for the template
        $idDominio = $pendenza['idDominio'] ?? (getenv('ID_DOMINIO') ?: '');
        $tipologie = [];
        if ($idDominio) {
            try {
                $repo = new EntrateRepository();
                $tipologie = $repo->listAbilitateByDominio($idDominio);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Define pendenza states for badges
        $pendenza_states = [
            'NON_ESEGUITA' => ['label' => 'Non eseguita', 'color' => 'secondary'],
            'ESEGUITA' => ['label' => 'Eseguita', 'color' => 'success'],
            'ESEGUITA_PARZIALE' => ['label' => 'Eseguita parziale', 'color' => 'warning'],
            'ANNULLATA' => ['label' => 'Annullata', 'color' => 'danger'],
            'SCADUTA' => ['label' => 'Scaduta', 'color' => 'dark'],
            'INCASSATA' => ['label' => 'Incassata', 'color' => 'info'],
        ];

        return $this->twig->render($response, 'pendenze/dettaglio.html.twig', [
            'idPendenza' => $idPendenza,
            'return_url' => $ret,
            'pendenza' => $pendenza,
            'error' => $error,
            'id_dominio' => $idDominio,
            'came_from_insert' => $cameFromInsert,
            'related_pendenze' => $relatedPendenze,
            'related_by_rata' => $relatedByRata,
            'current_rate' => $currentRate,
            'rate_info_source' => $rateInfoSource,
            'tipologie' => $tipologie,
            'pendenza_states' => $pendenza_states,
        ]);
    }

    public function downloadAvviso(Request $request, Response $response, array $args): Response
    {
        $idDominio = $args['idDominio'] ?? '';
        $numeroAvviso = $args['numeroAvviso'] ?? '';
        if ($idDominio === '' || $numeroAvviso === '') {
            $response->getBody()->write('Parametri mancanti');
            return $response->withStatus(400);
        }

        $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
        if (empty($backofficeUrl)) {
            $response->getBody()->write('GOVPAY_BACKOFFICE_URL non impostata');
            return $response->withStatus(500);
        }

        try {
            $username = getenv('GOVPAY_USER');
            $password = getenv('GOVPAY_PASSWORD');
            $guzzleOptions = [
                'headers' => ['Accept' => 'application/pdf'],
            ];
            $authMethod = getenv('AUTHENTICATION_GOVPAY');
            if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
                $cert = getenv('GOVPAY_TLS_CERT');
                $key = getenv('GOVPAY_TLS_KEY');
                $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
                if (!empty($cert) && !empty($key)) {
                    $guzzleOptions['cert'] = $cert;
                    $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
                } else {
                    $response->getBody()->write('mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati');
                    return $response->withStatus(500);
                }
            }
            if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                $guzzleOptions['auth'] = [$username, $password];
            }

            $http = new Client($guzzleOptions);
            $url = rtrim($backofficeUrl, '/') . '/avvisi/' . rawurlencode($idDominio) . '/' . rawurlencode($numeroAvviso);
            $resp = $http->request('GET', $url);
            $contentType = $resp->getHeaderLine('Content-Type') ?: 'application/pdf';
            $pdf = (string)$resp->getBody();
            $filename = 'avviso-' . $idDominio . '-' . $numeroAvviso . '.pdf';

            $response = $response
                ->withHeader('Content-Type', $contentType)
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Cache-Control', 'no-store');
            $response->getBody()->write($pdf);
            return $response;
        } catch (ClientException $ce) {
            $code = $ce->getResponse() ? $ce->getResponse()->getStatusCode() : 0;
            $msg = $code === 404 ? 'Avviso non trovato' : ('Errore client avviso: ' . $ce->getMessage());
            $response->getBody()->write($msg);
            return $response->withStatus($code ?: 500);
        } catch (\Throwable $e) {
            $response->getBody()->write('Errore scaricamento avviso: ' . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    /**
     * Scarica il PDF (o lo ritrasmette) che contiene gli avvisi associati a un documento.
     * Supporta query param 'numeriAvviso' come CSV o array, e 'inline' per aprire nel browser.
     */
    public function downloadAvvisiDocumento(Request $request, Response $response, array $args): Response
    {
        $idDominio = getenv('ID_DOMINIO') ?: '';
        $numeroDocumento = $args['numeroDocumento'] ?? '';
        if ($idDominio === '' || $numeroDocumento === '') {
            $response->getBody()->write('Parametri mancanti');
            return $response->withStatus(400);
        }

        // Gather numeriAvviso from query string or POST body and normalize
        $params = (array)$request->getQueryParams();
        $numeri = [];
        if (!empty($params['numeriAvviso'])) {
            if (is_array($params['numeriAvviso'])) {
                $numeri = array_map('trim', $params['numeriAvviso']);
            } else {
                $numeri = array_filter(array_map('trim', explode(',', (string)$params['numeriAvviso'])));
            }
        }
        if (empty($numeri)) {
            $body = $request->getParsedBody();
            if (is_array($body) && !empty($body['numeriAvviso'])) {
                $numeri = is_array($body['numeriAvviso']) ? $body['numeriAvviso'] : array_filter(array_map('trim', explode(',', (string)$body['numeriAvviso'])));
            }
        }

        $inline = isset($params['inline']) && ($params['inline'] === '1' || $params['inline'] === 'true');

        // Validate numeriAvviso entries if present: must be 18 digits each (pattern from API)
        $invalid = [];
        foreach ($numeri as $n) {
            $s = trim((string)$n);
            if ($s === '') continue; // ignore blanks
            if (!preg_match('/^[0-9]{18}$/', $s)) {
                $invalid[] = $s;
            }
        }
        if (!empty($invalid)) {
            $response->getBody()->write('numeriAvviso non validi: ' . implode(', ', $invalid));
            return $response->withStatus(400);
        }

        // Use Pendenze v2 client for avvisi/documento (no ID_A2A required)
        $pendenzeHost = getenv('GOVPAY_PENDENZE_URL') ?: '';
        if (empty($pendenzeHost) || !class_exists('\GovPay\Pendenze\Api\PendenzeApi')) {
            $response->getBody()->write('Client Pendenze v2 non disponibile o GOVPAY_PENDENZE_URL non impostata');
            return $response->withStatus(500);
        }

        try {
            $config = new \GovPay\Pendenze\Configuration();
            $config->setHost(rtrim($pendenzeHost, '/'));
            $username = getenv('GOVPAY_USER');
            $password = getenv('GOVPAY_PASSWORD');
            if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                // Some generated clients accept basic auth via Configuration setters
                if (method_exists($config, 'setUsername')) $config->setUsername($username);
                if (method_exists($config, 'setPassword')) $config->setPassword($password);
            }
            $guzzleOptions = [];
            $authMethod = getenv('AUTHENTICATION_GOVPAY');
            if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
                $cert = getenv('GOVPAY_TLS_CERT');
                $key = getenv('GOVPAY_TLS_KEY');
                $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
                if (!empty($cert) && !empty($key)) {
                    $guzzleOptions['cert'] = $cert;
                    $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
                } else {
                    $response->getBody()->write('mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati');
                    return $response->withStatus(500);
                }
            }

            // Log parameters for debugging
            \App\Logger::getInstance()->info('downloadAvvisiDocumento: calling Pendenze v2', [
                'idDominio' => $idDominio,
                'numeroDocumento' => $numeroDocumento,
                'numeriAvviso' => $numeri
            ]);

            $httpClient = new Client($guzzleOptions);
            $pApi = new \GovPay\Pendenze\Api\PendenzeApi($httpClient, $config);
            // Pendenze v2 signature: getAvvisiDocumento($id_dominio, $numero_documento, $lingua_secondaria = null, $numeri_avviso = null)
            $result = $pApi->getAvvisiDocumento($idDominio, $numeroDocumento, null, (!empty($numeri) ? $numeri : null));

            if ($result instanceof \SplFileObject) {
                $stream = fopen((string)$result->getRealPath(), 'rb');
                $content = stream_get_contents($stream);
                fclose($stream);
                $disposition = $inline ? 'inline' : 'attachment';
                $filename = 'avvisi-' . $numeroDocumento . '.pdf';
                $response = $response->withHeader('Content-Type', 'application/pdf')
                    ->withHeader('Content-Disposition', $disposition . '; filename="' . $filename . '"')
                    ->withHeader('Cache-Control', 'no-store');
                $response->getBody()->write($content);
                return $response;
            }

            $response->getBody()->write('Risposta inattesa dal Backoffice');
            return $response->withStatus(502);
        } catch (\GovPay\Pendenze\ApiException $e) {
            $code = $e->getCode() ?: 502;
            $body = method_exists($e, 'getResponseBody') ? $e->getResponseBody() : null;
            $obj = method_exists($e, 'getResponseObject') ? $e->getResponseObject() : null;
            $msg = is_object($obj) ? ($obj->descrizione ?? json_encode($obj)) : ($body ?? $e->getMessage());
            Logger::getInstance()->error('Errore getAvvisiDocumento (Pendenze v2 client)', ['code' => $code, 'body' => $body, 'exception' => $e->getMessage()]);
            $response->getBody()->write('Errore chiamata Pendenze v2: ' . $msg);
            return $response->withStatus($code);
        } catch (\Throwable $e) {
            Logger::getInstance()->error('Errore getAvvisiDocumento generico', ['error' => $e->getMessage()]);
            $response->getBody()->write('Errore chiamata Pendenze v2: ' . $e->getMessage());
            return $response->withStatus(502);
        }
    }

    public function downloadRicevuta(Request $request, Response $response, array $args): Response
    {
        $idDominio = $args['idDominio'] ?? '';
        $iuv = $args['iuv'] ?? '';
        $ccp = $args['ccp'] ?? '';
        if ($idDominio === '' || $iuv === '' || $ccp === '') {
            $response->getBody()->write('Parametri mancanti');
            return $response->withStatus(400);
        }

        $pendenzeUrl = getenv('GOVPAY_PENDENZE_URL') ?: '';
        if (empty($pendenzeUrl)) {
            $response->getBody()->write('GOVPAY_PENDENZE_URL non impostata');
            return $response->withStatus(500);
        }

        try {
            $username = getenv('GOVPAY_USER');
            $password = getenv('GOVPAY_PASSWORD');
            $guzzleOptions = [
                'headers' => ['Accept' => 'application/pdf'],
            ];
            $authMethod = getenv('AUTHENTICATION_GOVPAY');
            if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
                $cert = getenv('GOVPAY_TLS_CERT');
                $key = getenv('GOVPAY_TLS_KEY');
                $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
                if (!empty($cert) && !empty($key)) {
                    $guzzleOptions['cert'] = $cert;
                    $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
                } else {
                    $response->getBody()->write('mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati');
                    return $response->withStatus(500);
                }
            }
            if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                $guzzleOptions['auth'] = [$username, $password];
            }

            $http = new Client($guzzleOptions);
            $url = rtrim($pendenzeUrl, '/') . '/rpp/'
                . rawurlencode($idDominio) . '/' . rawurlencode($iuv) . '/' . rawurlencode($ccp) . '/rt';
            $resp = $http->request('GET', $url);
            $contentType = $resp->getHeaderLine('Content-Type') ?: 'application/pdf';
            $pdf = (string)$resp->getBody();
            $filename = 'rt-' . $iuv . '-' . $ccp . '.pdf';

            $response = $response
                ->withHeader('Content-Type', $contentType)
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Cache-Control', 'no-store');
            $response->getBody()->write($pdf);
            return $response;
        } catch (ClientException $ce) {
            $code = $ce->getResponse() ? $ce->getResponse()->getStatusCode() : 0;
            $msg = $code === 404 ? 'Ricevuta non trovata' : ('Errore client ricevuta: ' . $ce->getMessage());
            $response->getBody()->write($msg);
            return $response->withStatus($code ?: 500);
        } catch (\Throwable $e) {
            $response->getBody()->write('Errore scaricamento ricevuta: ' . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    public function downloadDominioLogo(Request $request, Response $response, array $args): Response
    {
        $idDominio = $args['idDominio'] ?? '';
        if ($idDominio === '') {
            $response->getBody()->write('Parametri mancanti');
            return $response->withStatus(400);
        }

        $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
        if (empty($backofficeUrl)) {
            $response->getBody()->write('GOVPAY_BACKOFFICE_URL non impostata');
            return $response->withStatus(500);
        }

        $username = getenv('GOVPAY_USER');
        $password = getenv('GOVPAY_PASSWORD');
        $guzzleOptions = [
            'headers' => ['Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*;q=0.8,*/*;q=0.5'],
        ];
        $authMethod = getenv('AUTHENTICATION_GOVPAY');
        if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
            $cert = getenv('GOVPAY_TLS_CERT');
            $key = getenv('GOVPAY_TLS_KEY');
            $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
            if (!empty($cert) && !empty($key)) {
                $guzzleOptions['cert'] = $cert;
                $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
            } else {
                $response->getBody()->write('mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati');
                return $response->withStatus(500);
            }
        }
        if ($username !== false && $password !== false && $username !== '' && $password !== '') {
            $guzzleOptions['auth'] = [$username, $password];
        }

        try {
            $http = new Client($guzzleOptions);
            $url = rtrim($backofficeUrl, '/') . '/domini/' . rawurlencode($idDominio) . '/logo';
            $resp = $http->request('GET', $url);
            $contentType = $resp->getHeaderLine('Content-Type') ?: 'image/png';
            $bytes = (string)$resp->getBody();
            $filename = 'logo-' . $idDominio;

            $response = $response
                ->withHeader('Content-Type', $contentType)
                ->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->withHeader('Cache-Control', 'no-store');
            $response->getBody()->write($bytes);
            return $response;
        } catch (ClientException $ce) {
            // Continua con fallback
        } catch (\Throwable $e) {
            // Prosegue con fallback
        }

        try {
            if (!class_exists('GovPay\\Backoffice\\Api\\EntiCreditoriApi')) {
                $response->getBody()->write('Client Backoffice EntiCreditori non disponibile');
                return $response->withStatus(500);
            }

            $config = new BackofficeConfiguration();
            $config->setHost(rtrim($backofficeUrl, '/'));
            if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                $config->setUsername($username);
                $config->setPassword($password);
            }

            $httpClient = new Client($guzzleOptions);
            $entiApi = new \GovPay\Backoffice\Api\EntiCreditoriApi($httpClient, $config);
            $domRes = $entiApi->getDominio($idDominio);

            $logo = null;
            if (is_object($domRes)) {
                if (method_exists($domRes, 'getLogo')) {
                    $logo = $domRes->getLogo();
                } elseif (property_exists($domRes, 'logo')) {
                    $logo = $domRes->logo;
                }
            }
            if ($logo === null) {
                $domData = json_decode(json_encode($domRes), true);
                if (is_array($domData)) {
                    $logo = $domData['logo'] ?? null;
                }
            }

            if (!$logo || !is_string($logo)) {
                $response->getBody()->write('Logo non disponibile');
                return $response->withStatus(404);
            }

            if (preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.*)$/', $logo, $matches)) {
                $contentType = $matches[1];
                $bytes = base64_decode($matches[2], true);
                if ($bytes === false) {
                    $response->getBody()->write('Logo non valido');
                    return $response->withStatus(415);
                }

                $response = $response
                    ->withHeader('Content-Type', $contentType)
                    ->withHeader('Content-Disposition', 'inline; filename="logo-' . $idDominio . '"')
                    ->withHeader('Cache-Control', 'no-store');
                $response->getBody()->write($bytes);
                return $response;
            }

            $bytes = base64_decode($logo, true);
            if ($bytes !== false && $bytes !== '') {
                $response = $response
                    ->withHeader('Content-Type', 'image/png')
                    ->withHeader('Content-Disposition', 'inline; filename="logo-' . $idDominio . '.png"')
                    ->withHeader('Cache-Control', 'no-store');
                $response->getBody()->write($bytes);
                return $response;
            }

            if (is_string($logo) && str_starts_with($logo, '/')) {
                try {
                    $http = new Client($guzzleOptions);
                    $url = rtrim($backofficeUrl, '/') . $logo;
                    $resp = $http->request('GET', $url);
                    $contentType = $resp->getHeaderLine('Content-Type') ?: 'image/png';
                    $bytes = (string)$resp->getBody();

                    $response = $response
                        ->withHeader('Content-Type', $contentType)
                        ->withHeader('Content-Disposition', 'inline; filename="logo-' . $idDominio . '"')
                        ->withHeader('Cache-Control', 'no-store');
                    $response->getBody()->write($bytes);
                    return $response;
                } catch (\Throwable $e) {
                    // Continua verso 404
                }
            }

            $response->getBody()->write('Logo non disponibile');
            return $response->withStatus(404);
        } catch (\Throwable $e) {
            $response->getBody()->write('Errore recupero logo: ' . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    private function exposeCurrentUser(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            $this->twig->getEnvironment()->addGlobal('current_user', $_SESSION['user']);
        }
    }

    private function shouldFallbackToRaw(\Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());
        return str_contains($message, 'invalid length')
            || str_contains($message, 'must be smaller than or equal to')
            || str_contains($message, 'length');
    }

    public function showEdit(Request $request, Response $response, array $args): Response
    {
        $this->exposeCurrentUser();

        $idPendenza = $args['idPendenza'] ?? '';
        $q = $request->getQueryParams();
        $returnUrl = $q['return'] ?? '/pendenze/ricerca';

        $error = null;
        $pendenza = null;

        // Recupera i dati della pendenza esistente
        $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
        $idA2A = getenv('ID_A2A') ?: '';
        if ($idPendenza === '') {
            $error = 'ID pendenza non specificato';
        } elseif (empty($backofficeUrl)) {
            $error = 'Variabile GOVPAY_BACKOFFICE_URL non impostata';
        } elseif ($idA2A === '') {
            $error = 'Variabile ID_A2A non impostata nel file .env';
        } else {
            try {
                $username = getenv('GOVPAY_USER');
                $password = getenv('GOVPAY_PASSWORD');
                $guzzleOptions = [
                    'headers' => ['Accept' => 'application/json'],
                ];
                $authMethod = getenv('AUTHENTICATION_GOVPAY');
                if ($authMethod !== false && strtolower((string)$authMethod) === 'sslheader') {
                    $cert = getenv('GOVPAY_TLS_CERT');
                    $key = getenv('GOVPAY_TLS_KEY');
                    $keyPass = getenv('GOVPAY_TLS_KEY_PASSWORD') ?: null;
                    if (!empty($cert) && !empty($key)) {
                        $guzzleOptions['cert'] = $cert;
                        $guzzleOptions['ssl_key'] = $keyPass ? [$key, $keyPass] : $key;
                    } else {
                        $error = 'mTLS abilitato ma GOVPAY_TLS_CERT/GOVPAY_TLS_KEY non impostati';
                    }
                }
                if (!$error && $username !== false && $password !== false && $username !== '' && $password !== '') {
                    $guzzleOptions['auth'] = [$username, $password];
                }

                if (!$error) {
                    $http = $this->makeHttpClient($guzzleOptions);
                    $url = rtrim($backofficeUrl, '/') . '/pendenze/' . rawurlencode((string)$idA2A) . '/' . rawurlencode($idPendenza);
                    $resp = $http->request('GET', $url);
                    $json = (string)$resp->getBody();
                    $data = json_decode($json, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \RuntimeException('Parsing JSON fallito: ' . json_last_error_msg());
                    }
                    $pendenza = $data;
                }
            } catch (ClientException $ce) {
                $code = $ce->getResponse() ? $ce->getResponse()->getStatusCode() : 0;
                $error = $code === 404 ? 'Pendenza non trovata (404)' : 'Errore client nella chiamata pendenza: ' . $ce->getMessage();
            } catch (\Throwable $e) {
                $error = 'Errore chiamata pendenza: ' . $e->getMessage();
            }
        }

        // Se ci sono errori o la pendenza non è modificabile, reindirizza al dettaglio
        if ($error || !$pendenza || $pendenza['stato'] !== 'NON_ESEGUITA') {
            return $response->withHeader('Location', '/pendenze/dettaglio/' . rawurlencode($idPendenza) . '?error=' . rawurlencode($error ?: 'Pendenza non modificabile'))->withStatus(302);
        }

        // Recupera tipologie abilitate per il dominio
        $idDominio = getenv('ID_DOMINIO') ?: '';
        $tipologie = [];
        if ($idDominio) {
            try {
                $repo = new EntrateRepository();
                $tipologie = $repo->listAbilitateByDominio($idDominio);
            } catch (\Throwable $e) {
                $tipologie = [];
            }
        }

        // Prepara i dati per il form di modifica
        $old = $this->preparePendenzaForForm($pendenza);

        return $this->twig->render($response, 'pendenze/modifica.html.twig', [
            'tipologie_pendenze' => $tipologie,
            'id_dominio' => $idDominio,
            'id_a2a' => $idA2A,
            'old' => $old,
            'idPendenza' => $idPendenza,
            'return_url' => $returnUrl,
            'default_anno' => (int)date('Y'),
        ]);
    }

    public function annullaPendenza(Request $request, Response $response, array $args): Response
    {
        $this->exposeCurrentUser();

        $idPendenza = $args['idPendenza'] ?? '';
        $responseData = ['success' => false, 'error' => ''];

        if ($idPendenza === '') {
            $responseData['error'] = 'ID pendenza non specificato';
        } else {
            try {
                $result = $this->updatePendenzaStatus($idPendenza, 'ANNULLATA');
                if ($result['success']) {
                    $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Pendenza annullata con successo'];
                    return $response->withHeader('Location', '/pendenze/dettaglio/' . rawurlencode($idPendenza))->withStatus(302);
                } else {
                    $_SESSION['flash'][] = ['type' => 'error', 'text' => $result['error']];
                    return $response->withHeader('Location', '/pendenze/dettaglio/' . rawurlencode($idPendenza))->withStatus(302);
                }
            } catch (\Throwable $e) {
                $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore: ' . $e->getMessage()];
                return $response->withHeader('Location', '/pendenze/dettaglio/' . rawurlencode($idPendenza))->withStatus(302);
            }
        }

        // Fallback error
        $_SESSION['flash'][] = ['type' => 'error', 'text' => $responseData['error'] ?: 'Errore sconosciuto'];
        return $response->withHeader('Location', '/pendenze/dettaglio/' . rawurlencode($idPendenza))->withStatus(302);
    }

    public function riattivaPendenza(Request $request, Response $response, array $args): Response
    {
        $this->exposeCurrentUser();

        $idPendenza = $args['idPendenza'] ?? '';
        $responseData = ['success' => false, 'error' => ''];

        if ($idPendenza === '') {
            $responseData['error'] = 'ID pendenza non specificato';
        } else {
            try {
                $result = $this->updatePendenzaStatus($idPendenza, 'NON_ESEGUITA');
                if ($result['success']) {
                    $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Pendenza riattivata con successo'];
                    return $response->withHeader('Location', '/pendenze/dettaglio/' . rawurlencode($idPendenza))->withStatus(302);
                } else {
                    $_SESSION['flash'][] = ['type' => 'error', 'text' => $result['error']];
                    return $response->withHeader('Location', '/pendenze/dettaglio/' . rawurlencode($idPendenza))->withStatus(302);
                }
            } catch (\Throwable $e) {
                $_SESSION['flash'][] = ['type' => 'error', 'text' => 'Errore: ' . $e->getMessage()];
                return $response->withHeader('Location', '/pendenze/dettaglio/' . rawurlencode($idPendenza))->withStatus(302);
            }
        }

        // Fallback error
        $_SESSION['flash'][] = ['type' => 'error', 'text' => $responseData['error'] ?: 'Errore sconosciuto'];
        return $response->withHeader('Location', '/pendenze/dettaglio/' . rawurlencode($idPendenza))->withStatus(302);
    }

    public function aggiornaPendenza(Request $request, Response $response, array $args): Response
    {
        $this->exposeCurrentUser();

        $idPendenza = $args['idPendenza'] ?? '';
        $params = (array)($request->getParsedBody() ?? []);

        if ($idPendenza === '') {
            return $this->twig->render($response, 'pendenze/modifica.html.twig', [
                'errors' => ['ID pendenza non specificato'],
                'old' => $params,
                'idPendenza' => $idPendenza,
                'return_url' => $params['return_url'] ?? '/pendenze/ricerca',
            ]);
        }

        // Validazione e costruzione payload simile al metodo create
        $errors = [];
        $warnings = [];

        // Validazioni di base (causale, importo, ecc.)
        $causale = trim((string)($params['causale'] ?? ''));
        if ($causale === '') {
            $errors[] = 'Causale obbligatoria';
        }

        $importoRaw = $params['importo'] ?? '';
        if ($importoRaw === '' || !is_numeric(str_replace(',', '.', (string)$importoRaw))) {
            $errors[] = 'Importo non valido';
        }
        $importo = (float)str_replace(',', '.', (string)$importoRaw);

        // Se ci sono errori, ricarica il form
        if ($errors) {
            $idDominio = getenv('ID_DOMINIO') ?: '';
            $tipologie = [];
            if ($idDominio) {
                try {
                    $repo = new EntrateRepository();
                    $tipologie = $repo->listAbilitateByDominio($idDominio);
                } catch (\Throwable $e) {}
            }
            return $this->twig->render($response, 'pendenze/modifica.html.twig', [
                'errors' => $errors,
                'old' => $params,
                'tipologie_pendenze' => $tipologie,
                'id_dominio' => $idDominio,
                'id_a2a' => getenv('ID_A2A') ?: '',
                'idPendenza' => $idPendenza,
                'return_url' => $params['return_url'] ?? '/pendenze/ricerca',
                'default_anno' => (int)date('Y'),
            ]);
        }

        // Aggiorna direttamente via PUT completo
        \App\Logger::getInstance()->debug('Aggiorna pendenza via PUT diretto');
        $putResult = $this->fallbackFullPutUpdate($idPendenza, $params);
        if ($putResult['success']) {
            $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Pendenza aggiornata con successo'];
            // Sempre torna al dettaglio della pendenza aggiornata, indipendentemente dal return_url
            $redirectUrl = '/pendenze/dettaglio/' . rawurlencode($idPendenza);
            return $response->withHeader('Location', $redirectUrl)->withStatus(302);
        } else {
            $errors[] = 'Errore nell\'aggiornamento della pendenza (PUT): ' . \App\Logger::sanitizeErrorForDisplay(implode('; ', $putResult['errors'] ?? []));
        }

        // In caso di errore, ricarica il form
        $idDominio = getenv('ID_DOMINIO') ?: '';
        $tipologie = [];
        if ($idDominio) {
            try {
                $repo = new EntrateRepository();
                $tipologie = $repo->listAbilitateByDominio($idDominio);
            } catch (\Throwable $e) {}
        }
        return $this->twig->render($response, 'pendenze/modifica.html.twig', [
            'errors' => $errors,
            'old' => $params,
            'tipologie_pendenze' => $tipologie,
            'id_dominio' => $idDominio,
            'id_a2a' => getenv('ID_A2A') ?: '',
            'idPendenza' => $idPendenza,
            'return_url' => $params['return_url'] ?? '/pendenze/ricerca',
            'default_anno' => (int)date('Y'),
        ]);
    }

    /**
     * Fallback: recupera la pendenza corrente, fonde i campi modificati dal form e invia un PUT completo.
     */
    private function fallbackFullPutUpdate(string $idPendenza, array $params): array
    {
        $backofficeUrl = getenv('GOVPAY_BACKOFFICE_URL') ?: '';
        $idA2A = getenv('ID_A2A') ?: '';
        $errors = [];
        if ($backofficeUrl === '' || $idA2A === '') {
            return ['success' => false, 'errors' => ['Configurazione GovPay incompleta']];
        }

        try {
            // 1) GET pendenza corrente
            $http = $this->makeHttpClient();
            $getUrl = rtrim($backofficeUrl, '/') . '/pendenze/' . rawurlencode((string)$idA2A) . '/' . rawurlencode($idPendenza);
            $resp = $http->request('GET', $getUrl, [ 'headers' => ['Accept' => 'application/json'] ]);
            $cur = json_decode((string)$resp->getBody(), true);
            if (!is_array($cur)) {
                return ['success' => false, 'errors' => ['Risposta GET pendenza non valida']];
            }

            // 2) Filtra solo i campi accettati da PendenzaPut (per evitare UnrecognizedPropertyException)
            $allowedKeys = [
                'numeroAvviso','tassonomia','dataValidita','datiAllegati','tassonomiaAvviso','importo','dataScadenza',
                'dataPromemoriaScadenza','idUnitaOperativa','idDominio','allegati','dataCaricamento','annoRiferimento',
                'divisione','nome','causale','soggettoPagatore','dataNotificaAvviso','cartellaPagamento','documento',
                'proprieta','direzione','idTipoPendenza','voci'
            ];
            $put = array_intersect_key($cur, array_flip($allowedKeys));

            // 3) Merge: applica modifiche dal form
            if (isset($params['causale'])) $put['causale'] = trim((string)$params['causale']);
            if (isset($params['importo'])) $put['importo'] = (float)str_replace(',', '.', (string)$params['importo']);
            if (!empty($params['dataValidita'])) { $put['dataValidita'] = $params['dataValidita']; } else { unset($put['dataValidita']); }
            if (!empty($params['dataScadenza'])) { $put['dataScadenza'] = $params['dataScadenza']; } else { unset($put['dataScadenza']); }

            // idTipoPendenza: usa quello del form se presente, altrimenti preserva quello attuale
            $idTipoFromForm = trim((string)($params['idTipoPendenza'] ?? ''));
            $idTipoFromCur = '';
            if (isset($cur['idTipoPendenza'])) {
                $idTipoFromCur = (string)$cur['idTipoPendenza'];
            } elseif (isset($cur['tipo']['idTipoPendenza'])) {
                $idTipoFromCur = (string)$cur['tipo']['idTipoPendenza'];
            } elseif (isset($cur['tipoPendenza']['idTipoPendenza'])) {
                $idTipoFromCur = (string)$cur['tipoPendenza']['idTipoPendenza'];
            }
            $idTipoEffective = $idTipoFromForm !== '' ? $idTipoFromForm : $idTipoFromCur;
            if ($idTipoEffective !== '') {
                $put['idTipoPendenza'] = $idTipoEffective;
            }

            if (!empty($params['soggettoPagatore']) && is_array($params['soggettoPagatore'])) {
                $s = $params['soggettoPagatore'];
                $tipo = strtoupper((string)($s['tipo'] ?? 'F'));
                $anag = trim((string)($s['anagrafica'] ?? ''));
                $nome = trim((string)($s['nome'] ?? ''));
                $ident = trim((string)($s['identificativo'] ?? ''));
                $email = trim((string)($s['email'] ?? ''));
                $normalized = [ 'tipo' => $tipo, 'identificativo' => $ident ];
                $normalized['anagrafica'] = $tipo === 'F' ? trim(($nome !== '' ? $nome . ' ' : '') . $anag) : $anag;
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) $normalized['email'] = $email;
                $put['soggettoPagatore'] = $normalized;
            }

            // 4) Non inviare idPendenza/idA2A nel body (sono nell'URL)
            unset($put['idPendenza'], $put['idA2A']);
            // Assicura idDominio popolato: obbligatorio per PendenzaPut
            if (empty($put['idDominio'])) {
                $envDom = getenv('ID_DOMINIO') ?: '';
                if ($envDom !== '') {
                    $put['idDominio'] = $envDom;
                }
            }

            // 5) Invia PUT completo utilizzando l'helper esistente
            $res = $this->sendPendenzaToBackoffice($put, $idPendenza);
            return $res;
        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();
            return ['success' => false, 'errors' => $errors];
        }
    }

    private function updatePendenzaStatus(string $idPendenza, string $newStatus): array
    {
        $patchUrl = getenv('GOVPAY_PENDENZE_PATCH_URL') ?: getenv('GOVPAY_BACKOFFICE_URL');
        $idA2A = getenv('ID_A2A') ?: '';

        if (empty($patchUrl) || $idA2A === '') {
            return ['success' => false, 'error' => 'Configurazione GovPay incompleta (URL PATCH o ID_A2A mancanti)'];
        }

        try {
            $http = $this->makeHttpClient();
            $url = rtrim($patchUrl, '/') . '/pendenze/' . rawurlencode((string)$idA2A) . '/' . rawurlencode($idPendenza);

            // JSON Patch per aggiornare solo lo stato, come da documentazione e codice di esempio
            $payload = [
                [
                    'op'    => 'REPLACE',
                    'path'  => '/stato',
                    'value' => $newStatus
                ]
            ];

            $reqBody = json_encode($payload);

            \App\Logger::getInstance()->debug('PATCH status request preparing', [
                'url' => $url,
                'method' => 'PATCH',
                'body' => $reqBody
            ]);

            try {
                $resp = $http->patch($url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => '*/*'
                    ],
                    'body' => $reqBody,
                    'timeout' => 30,
                    'connect_timeout' => 10,
                    'http_errors' => false
                ]);

                $respBody = (string)$resp->getBody();
                \App\Logger::getInstance()->debug('PATCH status response received', [
                    'status' => $resp->getStatusCode(),
                    'body' => $respBody
                ]);

                if ($resp->getStatusCode() === 200) {
                    return ['success' => true];
                } else {
                    return ['success' => false, 'error' => 'Errore nell\'aggiornamento dello stato: HTTP ' . $resp->getStatusCode() . ' - ' . $respBody];
                }
            } catch (RequestException $e) {
                $resp = $e->getResponse();
                $respBody = $resp ? (string)$resp->getBody() : null;
                $respStatus = $resp ? $resp->getStatusCode() : null;
                \App\Logger::getInstance()->error('PATCH status request failed (RequestException)', [
                    'message' => $e->getMessage(),
                    'status' => $respStatus,
                    'response_body' => $respBody,
                    'url' => $url,
                    'body' => $reqBody
                ]);
                $errorMsg = $respBody ? \App\Logger::sanitizeErrorForDisplay($respBody) : $e->getMessage();
                return ['success' => false, 'error' => 'Errore chiamata GovPay: HTTP ' . ($respStatus ?? 'N/A') . ' - ' . $errorMsg];
            } catch (\Throwable $e) {
                \App\Logger::getInstance()->error('PATCH status request failed (Throwable)', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'url' => $url,
                    'body' => $reqBody
                ]);
                return ['success' => false, 'error' => 'Errore chiamata GovPay: ' . $e->getMessage()];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Errore chiamata GovPay: ' . $e->getMessage()];
        }
    }

    private function preparePendenzaForForm(array $pendenza): array
    {
        // Converte i dati della pendenza nel formato atteso dal form
        // idTipoPendenza può essere presente in strutture diverse: tipo.idTipoPendenza, tipoPendenza.idTipoPendenza, idTipoPendenza diretto, oppure nella prima voce come codiceEntrata
        $idTipo = '';
        if (isset($pendenza['tipo']['idTipoPendenza'])) {
            $idTipo = (string)$pendenza['tipo']['idTipoPendenza'];
        } elseif (isset($pendenza['tipoPendenza']['idTipoPendenza'])) {
            $idTipo = (string)$pendenza['tipoPendenza']['idTipoPendenza'];
        } elseif (isset($pendenza['idTipoPendenza'])) {
            $idTipo = (string)$pendenza['idTipoPendenza'];
        }

        $voci = $pendenza['voci'] ?? [];
        $causale = $pendenza['causale'] ?? '';
        if ($causale === '' && is_array($voci) && isset($voci[0]['descrizione'])) {
            // Fallback: usa la descrizione della prima voce come causale se la pendenza non ha causale diretta
            $causale = (string)$voci[0]['descrizione'];
        }

        $form = [
            'idTipoPendenza' => $idTipo,
            'causale' => $causale,
            'importo' => $pendenza['importo'] ?? '',
            'annoRiferimento' => $pendenza['annoRiferimento'] ?? '',
            'soggettoPagatore' => $pendenza['soggettoPagatore'] ?? [],
            'voci' => $voci,
            'dataValidita' => $pendenza['dataValidita'] ?? '',
            'dataScadenza' => $pendenza['dataScadenza'] ?? '',
        ];

        if (empty($form['idTipoPendenza'])) {
            \App\Logger::getInstance()->debug('preparePendenzaForForm: idTipoPendenza non trovato nelle chiavi standard', [
                'keys' => array_keys($pendenza),
            ]);
        }

        return $form;
    }
}

