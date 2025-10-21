<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use App\Controllers\PendenzeController;
use Slim\Views\Twig;

final class PendenzeDetailRateTest extends TestCase
{
    public function testShowDetailRendersRateInfo(): void
    {
        $twig = $this->getMockBuilder(Twig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        // Simula risposta backoffice contenente r.documento.rata
        $dummyPendenza = [
            'idPendenza' => 'GIL-12345',
            'documento' => [
                'rata' => [
                    ['indice' => 1, 'importo' => 50.00, 'dataValidita' => '2025-11-01', 'dataScadenza' => '2025-11-01'],
                    ['indice' => 2, 'importo' => 50.00, 'dataValidita' => '2025-12-01', 'dataScadenza' => '2025-12-01'],
                ],
            ],
        ];

        $twig->expects($this->once())
            ->method('render')
            ->with(
                $this->isInstanceOf(ResponseInterface::class),
                'pendenze/dettaglio.html.twig',
                $this->callback(function($vars) use ($dummyPendenza) {
                    return isset($vars['pendenza']) && $vars['pendenza']['documento']['rata'][0]['indice'] == 1;
                })
            )
            ->willReturn(new Response(200));

        // Mock Guzzle client by monkey-patching makeHttpClient via anonymous class
        $controller = new class($twig, null) extends PendenzeController {
            public function makeHttpClient(array $guzzleOptions = []): \GuzzleHttp\Client
            {
                $dummy = [
                    'idPendenza' => 'GIL-12345',
                    'documento' => ['rata' => [
                        ['indice' => 1, 'importo' => 50.0, 'dataValidita' => '2025-11-01', 'dataScadenza' => '2025-11-01'],
                        ['indice' => 2, 'importo' => 50.0, 'dataValidita' => '2025-12-01', 'dataScadenza' => '2025-12-01'],
                    ]]
                ];
                $mock = new \GuzzleHttp\Handler\MockHandler([
                    new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], json_encode($dummy)),
                ]);
                $handler = \GuzzleHttp\HandlerStack::create($mock);
                return new \GuzzleHttp\Client(['handler' => $handler]);
            }
        };

    // Assicuriamoci che le variabili d'ambiente richieste siano presenti
    putenv('GOVPAY_BACKOFFICE_URL=http://backoffice.test');
    putenv('ID_A2A=TEST-A2A');

    $req = new ServerRequest('GET', '/pendenze/dettaglio/GIL-12345');
        $resp = new Response(200);

        $result = $controller->showDetail($req, $resp, ['idPendenza' => 'GIL-12345']);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
