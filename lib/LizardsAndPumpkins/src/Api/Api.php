<?php

namespace LizardsAndPumpkins\MagentoConnector\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class Api
{
    const API_ENDPOINT_CATALOG_IMPORT = 'catalog_import/';
    const API_ENDPOINT_STOCK_UPDATE = 'multiple_product_stock_quantity/';
    const API_ENDPOINT_CONTENT_BLOCK_UPDATE = 'content_blocks/';

    /**
     * @var string
     */
    private $url;

    /**
     * @var Client
     */
    private $client;

    public function __construct(string $url)
    {
        $this->checkHost($url);

        $this->url = rtrim($url, '/') . '/';
    }

    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    public function triggerProductImport(string $filename)
    {
        $headers = ['Accept' => 'application/vnd.lizards-and-pumpkins.catalog_import.v1+json'];

        $url = $this->url . self::API_ENDPOINT_CATALOG_IMPORT;
        $this->sendApiRequestWithFilename($filename, $url, $headers);
    }

    public function triggerProductStockImport(string $filename)
    {
        $headers = ['Accept' => 'application/vnd.lizards-and-pumpkins.multiple_product_stock_quantity.v1+json'];

        $url = $this->url . self::API_ENDPOINT_STOCK_UPDATE;
        $this->sendApiRequestWithFilename($filename, $url, $headers);
    }

    /**
     * @param string   $id
     * @param string   $content
     * @param string[] $context
     * @param string[] $keyGeneratorParameters
     */
    public function triggerCmsBlockUpdate(string $id, string $content, array $context, array $keyGeneratorParameters)
    {
        if (!is_string($id)) {
            throw new InvalidUrlException();
        }

        $headers = ['Accept' => 'application/vnd.lizards-and-pumpkins.content_blocks.v1+json'];
        $url = $this->url . self::API_ENDPOINT_CONTENT_BLOCK_UPDATE . $id;
        $body = json_encode(array_merge(['content' => $content, 'context' => $context], $keyGeneratorParameters));

        $this->sendApiRequest($url, $headers, $body);
    }

    private function checkHost(string $url)
    {
        if (!is_string($url)) {
            throw new InvalidHostException('Host must be of type string.');
        }

        $urlParts = parse_url($url);
        if ($urlParts === false) {
            throw new InvalidHostException('URL seems to be  seriously malformed.');
        }

        if (empty($urlParts['scheme']) || $urlParts['scheme'] !== 'https') {
            // TODO comment
            #throw new InvalidHostException('Host should be called via HTTPS!');
        }

        if (empty($urlParts['host'])) {
            throw new InvalidHostException('Domain must be specified.');
        }
    }

    /**
     * @param string   $method
     * @param string   $url
     * @param string[] $headers
     * @param string   $body
     * @return Request
     */
    private function createHttpRequest(string $method, string $url, array $headers, string $body)
    {
        return new Request($method, $url, $headers, $body);
    }

    /**
     * @param string   $filename
     * @param string   $url
     * @param string[] $headers
     */
    private function sendApiRequestWithFilename(string $filename, string $url, array $headers)
    {
        $this->validateFilename($filename);
        $body = json_encode(['fileName' => $filename]);
        $this->sendApiRequest($url, $headers, $body);
    }

    /**
     * @param string   $url
     * @param string[] $headers
     * @param string   $body
     */
    private function sendApiRequest(string $url, array $headers, string $body)
    {
        $request = $this->createHttpRequest('PUT', $url, $headers, $body);
        $client = new Client();
        $response = $client->send($request);
        if ($response->getStatusCode() !== 202) {
            throw new RequestFailedException("Unexpected response body from $url:\n" . $response->getBody());
        }
    }

    /**
     * @param string $filename
     */
    private function validateFilename(string $filename)
    {
        $dir = dirname($filename);
        if ($dir != '.') {
            throw new \UnexpectedValueException(
                sprintf('Filename "%s" should be a filename, no path.', $filename)
            );
        }
    }
}
