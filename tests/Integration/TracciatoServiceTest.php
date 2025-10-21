<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GovPay\Backoffice\Configuration as BackofficeConfiguration;
use GovPay\Backoffice\Api\PendenzeApi as BackofficePendenzeApi;
use App\Services\TracciatoService;

final class TracciatoServiceTest extends TestCase
{
    public function testSendTracciatoWithMockClientReturnsSuccess(): void
    {
        // Prepara risposta finta dal Backoffice
        $respData = [
            'id' => 123,
            'nomeFile' => 'tracciato-test.csv',
            'stato' => 'ELABORATO',
        ];

        // Creiamo uno stub minimale che espone il metodo usato dal servizio.
        $apiStub = new class {
            public function addTracciatoPendenze($model, $stampaAvvisi)
            {
                return [
                    'id' => 123,
                    'nomeFile' => 'tracciato-test.csv',
                    'stato' => 'ELABORATO',
                ];
            }
        };

        // Assicuriamoci che la variabile di configurazione sia presente
    putenv('GOVPAY_BACKOFFICE_URL=http://backoffice.test');
    putenv('ID_A2A=CMONTESILVANO');
    putenv('ID_DOMINIO=DOM1');
    // Preparo uno stub che cattura il payload ricevuto
    $captured = new class {
        public $received = null;
        public function addTracciatoPendenze($body, $stampaAvvisi = true)
        {
            $this->received = $body;
            return ['id' => 123, 'stato' => 'ELABORATO'];
        }
    };
    $svc = new TracciatoService($captured);

        $merged = [
            'idDominio' => 'DOM1',
            'idPendenza' => 'GIL-TEST',
            'idTipoPendenza' => 'TEST',
            'causale' => 'Causale test',
            'soggettoPagatore' => ['tipo' => 'F', 'identificativo' => 'ABC123', 'anagrafica' => 'Test User'],
            'voci' => [ ['idVocePendenza' => '1', 'descrizione' => 'V', 'importo' => 10.0] ],
        ];
        $parts = [ ['indice' => 1, 'importo' => 5.0, 'dataValidita' => '2025-11-01', 'dataScadenza' => '2025-11-01'], ['indice' => 2, 'importo' => 5.0, 'dataValidita' => '2025-12-01', 'dataScadenza' => '2025-12-01'] ];

        $result = $svc->sendTracciato($merged, $parts, true);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('idTracciato', $result);
        $this->assertNotEmpty($result['idTracciato']);
        $this->assertArrayHasKey('response', $result);
        $this->assertIsArray($result['response']);
        $this->assertEquals(123, $result['response']['id']);
        // Verifichiamo che il payload inviato contenga i campi attesi. Il servizio
        // può passare un Model (generato) oppure un array; normalizziamo entrambe le
        // possibilità per ispezionare i campi inviati.
        $this->assertNotNull($captured->received);
        $sent = $captured->received;
        if (is_object($sent)) {
            // Se ci hanno passato il model, sanitizziamo usando l'ObjectSerializer del client
            $sent = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($sent);
        }
        $this->assertIsArray($sent);
        $this->assertArrayHasKey('idTracciato', $sent);
        $this->assertArrayHasKey('idDominio', $sent);
        $this->assertArrayHasKey('inserimenti', $sent);
        foreach ($sent['inserimenti'] as $ins) {
            $this->assertArrayHasKey('idA2A', $ins);
            $this->assertNotEmpty($ins['idA2A']);
            $this->assertArrayHasKey('idPendenza', $ins);
            $this->assertArrayHasKey('idDominio', $ins);
        }
    }

    public function testEmptyAnnullamentiAreNotSent(): void
    {
        putenv('GOVPAY_BACKOFFICE_URL=http://backoffice.test');
        putenv('ID_A2A=CMONTESILVANO');
        putenv('ID_DOMINIO=DOM1');

        $captured = new class {
            public $received = null;
            public function addTracciatoPendenze($body, $stampaAvvisi = true)
            {
                $this->received = $body;
                return ['id' => 124, 'stato' => 'ELABORATO'];
            }
        };

        $svc = new TracciatoService($captured);

        $merged = [
            'idDominio' => 'DOM1',
            'idPendenza' => 'GIL-TEST',
            'idTipoPendenza' => 'TEST',
            'causale' => 'Causale test',
            'soggettoPagatore' => ['tipo' => 'F', 'identificativo' => 'ABC123', 'anagrafica' => 'Test User'],
            'voci' => [ ['idVocePendenza' => '1', 'descrizione' => 'V', 'importo' => 10.0] ],
            // present in input but vuoto => deve essere rimosso
            'annullamenti' => [],
        ];
        $parts = [ ['indice' => 1, 'importo' => 10.0, 'dataValidita' => '2025-11-01', 'dataScadenza' => '2025-11-01'] ];

        $result = $svc->sendTracciato($merged, $parts, true);
        $this->assertTrue($result['success']);
        $this->assertNotNull($captured->received);
        $sent = $captured->received;
        if (is_object($sent)) {
            $sent = \GovPay\Backoffice\ObjectSerializer::sanitizeForSerialization($sent);
        }
        $this->assertIsArray($sent);
        $this->assertArrayNotHasKey('annullamenti', $sent);
    }
}
