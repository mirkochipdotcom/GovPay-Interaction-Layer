<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use App\Controllers\PendenzeController;
use Slim\Views\Twig;

final class PendenzeRateizzazioneTest extends TestCase
{
    public function testCreateRateizzazioneAttachesDocumentAndPreservesSoggetto(): void
    {
        // Original pendenza parameters (as would come from preview)
        $orig = [
            'idTipoPendenza' => 'TEST',
            'causale' => 'Causale di prova',
            'importo' => 100.00,
            'annoRiferimento' => 2025,
            'soggettoPagatore' => [
                'tipo' => 'F',
                'identificativo' => 'RSSMRA30A01H501I',
                'anagrafica' => 'Mario Rossi'
            ],
            'voci' => [
                [
                    'idVocePendenza' => '1',
                    'descrizione' => 'Voce 1',
                    'importo' => 100.00,
                ],
            ],
        ];

        // Rate parts that sum correctly
        $parts = [
            ['indice' => 1, 'importo' => 33.34, 'dataValidita' => '2025-11-01', 'dataScadenza' => '2025-11-01'],
            ['indice' => 2, 'importo' => 33.33, 'dataValidita' => '2025-12-01', 'dataScadenza' => '2025-12-01'],
            ['indice' => 3, 'importo' => 33.33, 'dataValidita' => '2026-01-01', 'dataScadenza' => '2026-01-01'],
        ];

        $request = new ServerRequest('POST', '/pendenze/create-rateizzazione');
        $request = $request->withParsedBody([
            'original_params' => json_encode($orig),
            'parts' => $parts,
        ]);

        $response = new Response(200);

        // Twig not used by createRateizzazione on success, but required by constructor
        $twig = $this->createMock(Twig::class);

        // Simuliamo il percorso che invia ogni rata come singola pendenza
        $controller = new class($twig) extends PendenzeController {
            public $called = [];
            public function sendPendenzaToBackoffice(array $payload, ?string $idPendenza = null): array
            {
                $this->called[] = $payload;
                return ['success' => true, 'idPendenza' => $idPendenza ?? ($payload['idPendenza'] ?? null), 'response' => null, 'errors' => []];
            }
        };

        putenv('GOVPAY_BACKOFFICE_URL=http://backoffice.test');
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();

        $res = $controller->createRateizzazione($request, $response);

        // Redirect verso preview/print multirata
        $this->assertEquals(302, $res->getStatusCode());
        // Session should contain the aggregated multi_rate_document
        $this->assertArrayHasKey('multi_rate_document', $_SESSION);
        $multi = $_SESSION['multi_rate_document'];
        $this->assertArrayHasKey('documento', $multi);
        $this->assertCount(3, $multi['documento']['rata']);
        $this->assertArrayHasKey('proprieta', $multi);
        $this->assertEquals(3, $multi['proprieta']['numeroRate']);
        $this->assertCount(3, $multi['proprieta']['rate']);
        $this->assertArrayHasKey('soggettoPagatore', $multi);
        $this->assertEquals('RSSMRA30A01H501I', $multi['soggettoPagatore']['identificativo']);
        // Ensure per-rate sends occurred
        $this->assertCount(3, $controller->called);
        // Verify that for each sent payload the sum of voci equals the rata importo
        foreach ($controller->called as $i => $payload) {
            $sumV = 0.0;
            foreach ($payload['voci'] as $vv) {
                $sumV += (float)$vv['importo'];
            }
            $this->assertEqualsWithDelta((float)$payload['importo'], $sumV, 0.01, "La somma delle voci per la rata {$i} deve corrispondere all'importo della rata");
        }
    }

    public function testCreateRateizzazioneSendsEachRataAsSinglePendenza(): void
    {
        $orig = [
            'idTipoPendenza' => 'TEST',
            'causale' => 'Causale di prova',
            'importo' => 100.00,
            'annoRiferimento' => 2025,
            'soggettoPagatore' => [
                'tipo' => 'F',
                'identificativo' => 'RSSMRA30A01H501I',
                'anagrafica' => 'Mario Rossi'
            ],
            'voci' => [
                [
                    'idVocePendenza' => '1',
                    'descrizione' => 'Voce 1',
                    'importo' => 100.00,
                ],
            ],
        ];

        $parts = [
            ['indice' => 1, 'importo' => 50.00, 'dataValidita' => '2025-11-01', 'dataScadenza' => '2025-11-01'],
            ['indice' => 2, 'importo' => 50.00, 'dataValidita' => '2025-12-01', 'dataScadenza' => '2025-12-01'],
        ];

        $request = new ServerRequest('POST', '/pendenze/create-rateizzazione');
        $request = $request->withParsedBody([
            'original_params' => json_encode($orig),
            'parts' => $parts,
        ]);
        $response = new Response(200);

        $twig = $this->createMock(Twig::class);
        $called = [];
        $controller = new class($twig) extends PendenzeController {
            public $called = [];
            public function sendPendenzaToBackoffice(array $payload, ?string $idPendenza = null): array
            {
                $this->called[] = $payload;
                return ['success' => true, 'idPendenza' => $idPendenza ?? ($payload['idPendenza'] ?? null), 'errors' => []];
            }
        };

        putenv('GOVPAY_BACKOFFICE_URL=http://backoffice.test');

        $res = $controller->createRateizzazione($request, $response);
        $this->assertEquals(302, $res->getStatusCode());
    $this->assertCount(2, $controller->called);
        // Check that each sent payload contains documento.rata integer
        foreach ($controller->called as $i => $payload) {
            $this->assertIsArray($payload);
            $this->assertArrayHasKey('documento', $payload);
            $this->assertEquals($parts[$i]['indice'], $payload['documento']['rata']);
            $this->assertArrayNotHasKey('return', $payload, 'Il campo UI "return" non deve essere inviato al Backoffice');
            if (isset($payload['proprieta'])) {
                $this->assertArrayNotHasKey('numeroRate', $payload['proprieta'], 'Il campo numeroRate non deve essere inviato nella proprieta per singola rata');
            }
            $this->assertArrayNotHasKey('idPendenza', $payload, 'idPendenza non deve essere inviato nel body della richiesta');
            $sumV = 0.0;
            foreach ($payload['voci'] as $vv) { $sumV += (float)$vv['importo']; }
            $this->assertEqualsWithDelta((float)$payload['importo'], $sumV, 0.01, 'La somma delle voci deve corrispondere all\'importo della rata');
        }
    }

    public function testCreateRateizzazioneRemovesNomeFromSoggettoPagatore(): void
    {
        $orig = [
            'idTipoPendenza' => 'TEST',
            'causale' => 'Causale di prova',
            'importo' => 100.00,
            'annoRiferimento' => 2025,
            'soggettoPagatore' => [
                'tipo' => 'F',
                'identificativo' => 'RSSMRA30A01H501I',
                'nome' => 'Mario',
                'anagrafica' => 'Rossi'
            ],
            'voci' => [
                [
                    'idVocePendenza' => '1',
                    'descrizione' => 'Voce 1',
                    'importo' => 100.00,
                ],
            ],
        ];

        $parts = [
            ['indice' => 1, 'importo' => 100.00, 'dataValidita' => '2025-11-01', 'dataScadenza' => '2025-11-01'],
        ];

        $request = new ServerRequest('POST', '/pendenze/create-rateizzazione');
        $request = $request->withParsedBody([
            'original_params' => json_encode($orig),
            'parts' => $parts,
        ]);
        $response = new Response(200);

        $twig = $this->createMock(Twig::class);
        $controller = new class($twig) extends PendenzeController {
            public $called = [];
            public function sendPendenzaToBackoffice(array $payload, ?string $idPendenza = null): array
            {
                $this->called[] = $payload;
                return ['success' => true, 'idPendenza' => $idPendenza ?? ($payload['idPendenza'] ?? null), 'response' => null, 'errors' => []];
            }
        };

        putenv('GOVPAY_BACKOFFICE_URL=http://backoffice.test');

        $res = $controller->createRateizzazione($request, $response);
        $this->assertEquals(302, $res->getStatusCode());
    $this->assertCount(1, $controller->called);
        $payload = $controller->called[0];
        $this->assertArrayHasKey('soggettoPagatore', $payload);
        $this->assertArrayNotHasKey('nome', $payload['soggettoPagatore']);
        $this->assertEquals('Mario Rossi', $payload['soggettoPagatore']['anagrafica']);
    $this->assertArrayNotHasKey('idPendenza', $payload, 'idPendenza non deve essere inviato nel body della richiesta');
    $sumV = 0.0; foreach ($payload['voci'] as $vv) { $sumV += (float)$vv['importo']; }
    $this->assertEqualsWithDelta((float)$payload['importo'], $sumV, 0.01, 'La somma delle voci deve corrispondere all\'importo della rata');
        if (isset($payload['proprieta'])) {
            $this->assertArrayNotHasKey('numeroRate', $payload['proprieta']);
        }
    }

    public function testCreateRateizzazioneRemovesEmptyCartellaPagamento(): void
    {
        $orig = [
            'idTipoPendenza' => 'TEST',
            'causale' => 'Causale di prova',
            'importo' => 100.00,
            'annoRiferimento' => 2025,
            'soggettoPagatore' => [
                'tipo' => 'F',
                'identificativo' => 'RSSMRA30A01H501I',
                'anagrafica' => 'Mario Rossi'
            ],
            'voci' => [
                [
                    'idVocePendenza' => '1',
                    'descrizione' => 'Voce 1',
                    'importo' => 100.00,
                ],
            ],
            // UI bug: empty array present
            'cartellaPagamento' => [],
        ];

        $parts = [
            ['indice' => 1, 'importo' => 100.00, 'dataValidita' => '2025-11-01', 'dataScadenza' => '2025-11-01'],
        ];

        $request = new ServerRequest('POST', '/pendenze/create-rateizzazione');
        $request = $request->withParsedBody([
            'original_params' => json_encode($orig),
            'parts' => $parts,
        ]);
        $response = new Response(200);

        $twig = $this->createMock(Twig::class);
        $controller = new class($twig) extends PendenzeController {
            public $called = [];
            public function sendPendenzaToBackoffice(array $payload, ?string $idPendenza = null): array
            {
                $this->called[] = $payload;
                return ['success' => true, 'idPendenza' => $idPendenza ?? ($payload['idPendenza'] ?? null), 'response' => null, 'errors' => []];
            }
        };

        putenv('GOVPAY_BACKOFFICE_URL=http://backoffice.test');

        $res = $controller->createRateizzazione($request, $response);
        $this->assertEquals(302, $res->getStatusCode());
    $this->assertCount(1, $controller->called);
        $payload = $controller->called[0];
    $this->assertArrayNotHasKey('cartellaPagamento', $payload, 'cartellaPagamento vuota non deve essere inviata');
    $sumV = 0.0; foreach ($payload['voci'] as $vv) { $sumV += (float)$vv['importo']; }
    $this->assertEqualsWithDelta((float)$payload['importo'], $sumV, 0.01, 'La somma delle voci deve corrispondere all\'importo della rata');
    }

    public function testCreateRateizzazioneSumMismatchReturnsFormWithError(): void
    {
        $orig = [
            'idTipoPendenza' => 'TEST',
            'causale' => 'Causale di prova',
            'importo' => 100.00,
            'annoRiferimento' => 2025,
            'soggettoPagatore' => [
                'tipo' => 'F',
                'identificativo' => 'RSSMRA30A01H501I',
                'anagrafica' => 'Mario Rossi'
            ],
            'voci' => [
                [
                    'idVocePendenza' => '1',
                    'descrizione' => 'Voce 1',
                    'importo' => 100.00,
                ],
            ],
        ];

        // Parts that do NOT sum to 100
        $parts = [
            ['indice' => 1, 'importo' => 20.00, 'dataValidita' => '2025-11-01', 'dataScadenza' => '2025-11-01'],
            ['indice' => 2, 'importo' => 20.00, 'dataValidita' => '2025-12-01', 'dataScadenza' => '2025-12-01'],
        ];

        $request = new ServerRequest('POST', '/pendenze/create-rateizzazione');
        $request = $request->withParsedBody([
            'original_params' => json_encode($orig),
            'parts' => $parts,
        ]);

        $response = new Response(200);

        // Prepare a Twig mock which will capture the render() call
        $twig = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $twig->expects($this->once())
            ->method('render')
            ->with(
                $this->isInstanceOf(ResponseInterface::class),
                'pendenze/rateizzazione.html.twig',
                $this->callback(function($vars) {
                    // Expect an 'errors' key with our mismatch message
                    if (!is_array($vars)) return false;
                    if (!isset($vars['errors'])) return false;
                    if (!is_array($vars['errors'])) return false;
                    return count($vars['errors']) > 0 && str_contains($vars['errors'][0], 'La somma delle rate');
                })
            )
            ->willReturn(new Response(200));

        $controller = new PendenzeController($twig);

        $resp = $controller->createRateizzazione($request, $response);
        $this->assertInstanceOf(ResponseInterface::class, $resp);
        $this->assertEquals(200, $resp->getStatusCode());
    }
}
