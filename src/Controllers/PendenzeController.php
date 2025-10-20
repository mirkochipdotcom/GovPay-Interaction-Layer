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
use GovPay\Backoffice\Api\PendenzeApi as BackofficePendenzeApi;
use GovPay\Backoffice\Configuration as BackofficeConfiguration;
use GovPay\Backoffice\Model\RaggruppamentoStatistica;
use GovPay\Backoffice\Model\StatoPendenza;
use GovPay\Backoffice\ObjectSerializer as BackofficeSerializer;
use GovPay\Pendenze\Api\PendenzeApi;
use GovPay\Pendenze\Configuration as PendenzeConfiguration;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class PendenzeController
{
    public function __construct(private readonly Twig $twig)
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
        $identificativo = trim((string)($sog['identificativo'] ?? ''));
        $anagrafica = trim((string)($sog['anagrafica'] ?? ''));
        $nome = trim((string)($sog['nome'] ?? ''));
        $email = trim((string)($sog['email'] ?? ''));
        if ($identificativo === '') $errors[] = 'Codice fiscale / Partita IVA obbligatorio';
        if ($anagrafica === '') $errors[] = ($tipoSog === 'F') ? 'Cognome obbligatorio' : 'Ragione sociale obbligatoria';

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
        if (!empty($params['dataValidita'])) $payload['dataValidita'] = $params['dataValidita'];
        if (!empty($params['dataScadenza'])) $payload['dataScadenza'] = $params['dataScadenza'];
        foreach (['direzione', 'divisione', 'cartellaPagamento'] as $f) {
            if (!empty($params[$f])) $payload[$f] = $params[$f];
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

        try {
            $httpClient = new Client($guzzleOptions);

            // idPendenza: se fornito usalo, altrimenti genera uno identificativo client-side
            $idPendenzaRaw = trim((string)($params['idPendenza'] ?? ''));
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
            // Sanitizza l'id per rispettare il pattern (solo lettere, numeri, - e _)
            $idPendenzaSanitized = preg_replace('/[^A-Za-z0-9\-_]/', '-', $idPendenzaCand);
            $idPendenzaSanitized = substr($idPendenzaSanitized, 0, 35);

            $url = rtrim($backofficeUrl, '/') . '/pendenze/' . rawurlencode((string)$idA2A) . '/' . rawurlencode($idPendenzaSanitized);
            $username = getenv('GOVPAY_USER');
            $password = getenv('GOVPAY_PASSWORD');
            $requestOptions = [
                'headers' => ['Accept' => 'application/json'],
                'json' => $payload,
            ];
            if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                $requestOptions['auth'] = [$username, $password];
            }

            // Log payload per debug
            if ((getenv('APP_DEBUG') !== false) && getenv('APP_DEBUG')) {
                Logger::getInstance()->debug('Pendenze PUT ' . $url, ['payload' => $payload]);
            }
            $resp = $httpClient->request('PUT', $url, $requestOptions);
            $code = $resp->getStatusCode();
            $body = (string)$resp->getBody();
            $data = json_decode($body, true);

            // Mostra eventuali avvisi di sanitizzazione prima del messaggio di successo
            if (!empty($warnings)) {
                foreach ($warnings as $w) {
                    $_SESSION['flash'][] = ['type' => 'warning', 'text' => $w];
                }
            }
            $_SESSION['flash'][] = ['type' => 'success', 'text' => 'Pendenza creata con successo'];
            // Preferiamo usare l'idPendenza che abbiamo generato o fornito, altrimenti cerchiamo quello restituito
            $newId = $idPendenzaSanitized;
            if (empty($newId)) {
                $newId = $data['idPendenza'] ?? $data['id_pendenza'] ?? $data['id'] ?? null;
            }
            if ($newId) {
                // Redirect to dettaglio e segnala che proveniamo da un inserimento
                $base = '/pendenze/dettaglio/' . rawurlencode((string)$newId);
                $query = ['from' => 'insert'];
                if (!empty($params['return'])) {
                    $query['return'] = $params['return'];
                }
                $location = $base . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
                return $response->withHeader('Location', $location)->withStatus(302);
            }
            return $response->withHeader('Location', '/pendenze/ricerca')->withStatus(302);
        } catch (ClientException $ce) {
            $detail = $ce->getResponse() ? (string)$ce->getResponse()->getBody() : '';
            if ($detail !== '') {
                $errors[] = 'Errore API: ' . $detail;
            } else {
                $errors[] = 'Errore API: ' . $ce->getMessage();
            }
        } catch (\Throwable $e) {
            $errors[] = 'Errore durante l\'invio: ' . $e->getMessage();
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

                        $requestOptions = [
                            'headers' => ['Accept' => 'application/json'],
                            'query' => $query,
                        ];
                        if ($username !== false && $password !== false && $username !== '' && $password !== '') {
                            $requestOptions['auth'] = [$username, $password];
                        }

                        $resp = $httpClient->request('GET', $url, $requestOptions);
                        $json = (string)$resp->getBody();
                        $dataArr = json_decode($json, true);
                        if (json_last_error() !== JSON_ERROR_NONE || !is_array($dataArr)) {
                            throw new \RuntimeException('Parsing JSON fallito: ' . json_last_error_msg());
                        }

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
        ]);
    }

    public function showBulkInsert(Request $request, Response $response): Response
    {
        $this->exposeCurrentUser();
        return $this->twig->render($response, 'pendenze/inserimento_massivo.html.twig');
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
                    $http = new Client($guzzleOptions);
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

        return $this->twig->render($response, 'pendenze/dettaglio.html.twig', [
            'idPendenza' => $idPendenza,
            'return_url' => $ret,
            'pendenza' => $pendenza,
            'error' => $error,
            'id_dominio' => $pendenza['idDominio'] ?? (getenv('ID_DOMINIO') ?: ''),
            'came_from_insert' => $cameFromInsert,
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
}

