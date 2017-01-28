<?php

declare(strict_types=1);

namespace LizardsAndPumpkins\MagentoConnector\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;

class GuzzleAdapter implements HttpApiClient
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function putRequest(string $url, string $body, array $headers): string
    {
        $this->validateUrl($url);
        $method = 'PUT';
        $response = $this->sendRequest($url, $method, $body, $headers);

        if ($response->getStatusCode() !== 202) {
            throw new RequestFailedException(
                sprintf('Status code %s does not match expected 202.', $response->getStatusCode())
            );
        }
        return (string)$response->getBody();
    }

    public function getRequest(string $url, string $body, array $headers): string
    {
        $this->validateUrl($url);
        $method = 'GET';
        $response = $this->sendRequest($url, $method, $body, $headers);

        if ($response->getStatusCode() !== 200) {
            throw new RequestFailedException(
                sprintf('Status code %s does not match expected 200.', $response->getStatusCode())
            );
        }
        return (string)$response->getBody();
    }

    private function validateUrl(string $url)
    {
        if ($url === '') {
            throw new InvalidHostException('Url must not be empty.');
        }

        $parts = parse_url($url);
        if ($parts === false) {
            throw new InvalidHostException('URL seems to be  seriously malformed.');
        }

        if (empty($parts['host'])) {
            throw new InvalidHostException('Host must be specified.');
        }

        if ($parts['scheme'] !== 'http' && $parts['scheme'] !== 'https') {
            throw new InvalidHostException('Url must either be http or https.');
        }
    }

    /**
     * @param string $url
     * @param        $method
     * @param string $body
     * @param array  $headers
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    private function sendRequest(string $url, $method, string $body, array $headers)
    {
        try {
            $response = $this->client->send(new Request($method, $url, $headers, $body));
        } catch (ClientException $e) {
            throw new RequestFailedException($e->getMessage(), $e->getCode(), $e);
        } catch (ServerException $e) {
            throw new RequestFailedException($e->getMessage(), $e->getCode(), $e);
        }
        return $response;
    }
}
