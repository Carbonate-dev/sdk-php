<?php

namespace Carbonate\Api;

use Carbonate\Exceptions\ApiException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;

class Client
{
    private $apiUrl;

    /**
     * @var GuzzleClient|null
     */
    private $client;

    /**
     * @var string
     */
    private $apiUserId;

    /**
     * @var string
     */
    private $apiKey;

    public function __construct(
        string $apiUserId = null,
        string $apiKey = null,
        string $apiUrl = null,
        GuzzleClient $client = null
    ) {
        $this->apiUserId = $apiUserId ?: getenv('CARBONATE_USER_ID');
        $this->apiKey = $apiKey ?: getenv('CARBONATE_API_KEY');
        $this->apiUrl = $apiUrl ?: 'https://api.carbonate.dev/';
        $this->client = $client ?: new GuzzleClient();

        if (!$this->apiUserId) {
            throw new \InvalidArgumentException('No username provided, please either pass in $apiUserId to the constructor or set the CARBONATE_USER_ID environment variable');
        }

        if (!$this->apiKey) {
            throw new \InvalidArgumentException('No API key provided, please either pass in $apiKey to the constructor or set the CARBONATE_API_KEY environment variable');
        }
    }

    public function callApi($url, array $data)
    {
        if (!isset($data['test_name'])) {
            throw new \Exception('No test name provided, please call start_test() with your test name');
        }

        try {
            $response = $this->client->post($this->apiUrl . $url, [
                'headers' => [
                    'X-Api-User-Id' => $this->apiUserId,
                    'X-Api-Key' => $this->apiKey,
                ],
                \GuzzleHttp\RequestOptions::JSON => $data,
            ]);

            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            }
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }

        $body = $response->getBody();
        $statusCode = $response->getStatusCode();

        throw new ApiException("Call to ${url} failed with status code ${statusCode}, body: ${body}");
    }

    public function extractActions($testName, $instruction, $html): array
    {
        return $this->callApi('actions/extract', [
            'test_name' => $testName,
            'story' => $instruction,
            'html' => $html,
        ]);
    }

    public function extractAssertions($testName, $instruction, $html): array
    {
        return $this->callApi('assertions/extract', [
            'test_name' => $testName,
            'story' => $instruction,
            'html' => $html,
        ]);
    }

    public function extractLookup($testName, $instruction, $html): ?array
    {
        return $this->callApi('lookup/extract', [
            'test_name' => $testName,
            'story' => $instruction,
            'html' => $html,
        ]);
    }

    public function uploadRecording($testName, array $recording, \DateTimeInterface $startedAt, array $actionIds, array $assertionIds, array $lookupIds): ?bool
    {
        return $this->callApi('test/recording', [
            'test_name' => $testName,
            'actions' => $actionIds,
            'assertions' => $assertionIds,
            'lookups' => $lookupIds,
            'started_at' => $startedAt->format(\DateTimeInterface::ATOM),
            'packed' => base64_encode(gzencode(json_encode($recording))),
        ]);
    }
}
