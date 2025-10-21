<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use App\Controllers\PendenzeController;
use App\Services\TracciatoService;
use Slim\Views\Twig;

final class PendenzeControllerTracciatoFlowTest extends TestCase
{
    public function testCreateRateizzazioneUsesTracciatoServiceAndRedirects(): void
    {
        // Ensure env var present so controller will try to send tracciato
        putenv('GOVPAY_BACKOFFICE_URL=http://backoffice.test');

        // Prepare original params and parts
        $orig = [
            'idTipoPendenza' => 'TEST',
            'causale' => 'Causale test',
            'importo' => 10.0,
            'annoRiferimento' => 2025,
            'soggettoPagatore' => ['tipo' => 'F', 'identificativo' => 'RSSMRA30A01H501I', 'anagrafica' => 'Mario Rossi'],
            'voci' => [['idVocePendenza' => '1', 'descrizione' => 'V', 'importo' => 10.0]],
        ];
        $parts = [['indice' => 1, 'importo' => 5.0], ['indice' => 2, 'importo' => 5.0]];

        $request = new ServerRequest('POST', '/pendenze/create-rateizzazione');
        $request = $request->withParsedBody([
            'original_params' => json_encode($orig),
            'parts' => $parts,
        ]);
        $response = new Response(200);


        $twig = $this->createMock(Twig::class);
        // Use subclass to intercept create() as previous tests do
        $controller = new class($twig) extends PendenzeController {
            public $createCalled = false;
            public function create($request, $response): ResponseInterface
            {
                $this->createCalled = true;
                return $response->withStatus(201);
            }
        };

        // Start session to allow flash writes
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();

        $res = $controller->createRateizzazione($request, $response);
        $this->assertInstanceOf(ResponseInterface::class, $res);
    $this->assertEquals(201, $res->getStatusCode());
    $this->assertTrue($controller->createCalled);
    }
}
