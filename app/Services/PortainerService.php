<?php
/**
 * SPDX-License-Identifier: EUPL-1.2
 * License: European Union Public Licence v1.2 (EUPL-1.2)
 */

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class PortainerService
{
    private string $baseUrl;
    private string $apiToken;
    private Client $client;

    public function __construct()
    {
        $this->baseUrl  = rtrim((string)(getenv('PORTAINER_URL') ?: ''), '/');
        $this->apiToken = (string)(getenv('PORTAINER_API_TOKEN') ?: '');

        // verify: false equivale a curl -k (necessario per certificati self-signed come su 10.x.x.x:9443)
        $this->client = new Client([
            'timeout'         => 10,
            'connect_timeout' => 5,
            'verify'          => false,
            'headers'         => [
                'X-API-Key' => $this->apiToken,
                'Accept'    => 'application/json',
            ],
        ]);
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->apiToken !== '';
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Restituisce lo stato Portainer: endpoint attivi e lista container.
     *
     * @return array{ok?: bool, endpoint?: string, endpoint_id?: int, containers?: array, error?: string, message?: string, url?: string}
     */
    public function getStatus(): array
    {
        if (!$this->isConfigured()) {
            return [
                'error'   => 'not_configured',
                'message' => 'PORTAINER_URL e/o PORTAINER_API_TOKEN non impostati',
                'url'     => $this->baseUrl,
            ];
        }

        try {
            $endpointsResp = $this->client->get($this->baseUrl . '/api/endpoints');
            $endpoints = json_decode((string)$endpointsResp->getBody(), true) ?? [];

            if (empty($endpoints)) {
                return ['error' => 'no_endpoints', 'message' => 'Nessun endpoint trovato su Portainer'];
            }

            $endpoint   = $endpoints[0];
            $endpointId = (int)$endpoint['Id'];

            $containersResp = $this->client->get(
                $this->baseUrl . "/api/endpoints/{$endpointId}/docker/containers/json",
                ['query' => ['all' => 1]]
            );
            $rawContainers = json_decode((string)$containersResp->getBody(), true) ?? [];

            $containers = array_map(static function (array $c): array {
                return [
                    'id'     => substr($c['Id'] ?? '', 0, 12),
                    'name'   => ltrim($c['Names'][0] ?? '?', '/'),
                    'image'  => $c['Image'] ?? '',
                    'state'  => $c['State'] ?? '',
                    'status' => $c['Status'] ?? '',
                ];
            }, $rawContainers);

            // Ordina: running prima, poi per nome
            usort($containers, static function (array $a, array $b): int {
                if ($a['state'] === $b['state']) {
                    return strcmp($a['name'], $b['name']);
                }
                return $a['state'] === 'running' ? -1 : 1;
            });

            return [
                'ok'          => true,
                'endpoint'    => $endpoint['Name'] ?? 'default',
                'endpoint_id' => $endpointId,
                'containers'  => $containers,
            ];

        } catch (ConnectException $e) {
            return [
                'error'   => 'connection_failed',
                'message' => 'Impossibile contattare Portainer (' . $this->baseUrl . '): ' . $e->getMessage(),
                'url'     => $this->baseUrl,
            ];
        } catch (RequestException $e) {
            $code = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
            return [
                'error'   => 'request_failed',
                'message' => "Portainer ha risposto con HTTP {$code}: " . $e->getMessage(),
                'url'     => $this->baseUrl,
            ];
        } catch (\Throwable $e) {
            return [
                'error'   => 'unexpected',
                'message' => $e->getMessage(),
                'url'     => $this->baseUrl,
            ];
        }
    }
}
