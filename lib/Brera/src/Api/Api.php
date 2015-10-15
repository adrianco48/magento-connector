<?php

namespace Brera\MagentoConnector\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

// TODO fix autoloading
require_once __DIR__ . '/../../../guzzlehttp/psr7/src/functions.php';
require_once __DIR__ . '/../../../guzzlehttp/guzzle/src/functions.php';
require_once __DIR__ . '/../../../guzzlehttp/promises/src/functions.php';

class Api
{
    const API_ENDPOINT_CATALOG_IMPORT = 'catalog_import/';
    const API_ENDPOINT_STOCK_UPDATE = 'multiple_product_stock_quantity/';
    /**
     * @var string
     */
    private $url;

    /**
     * @param string $url
     */
    public function __construct($url)
    {
        $this->checkHost($url);

        $this->url = rtrim($url, '/') . '/';
    }

    /**
     * @param string $filename
     */
    public function triggerProductImport($filename)
    {
        $headers = ['Accept' => 'application/vnd.brera.catalog_import.v1+json'];

        $url = $this->url . self::API_ENDPOINT_CATALOG_IMPORT;
        $this->sendApiRequest($filename, $url, $headers);
    }

    /**
     * @param string $filename
     */
    public function triggerProductStockImport($filename)
    {
        $headers = ['Accept' => 'application/vnd.brera.multiple_product_stock_quantity.v1+json'];

        $url = $this->url . self::API_ENDPOINT_STOCK_UPDATE;
        $this->sendApiRequest($filename, $url, $headers);
    }

    /**
     * @param string $url
     */
    private function checkHost($url)
    {
        if (!is_string($url)) {
            throw new InvalidHostException('Host must be of type string.');
        }

        $urlParts = parse_url($url);
        if ($urlParts === false) {
            throw new InvalidHostException('URL seems to be  seriously malformed.');
        }

        if (empty($urlParts['scheme']) || $urlParts['scheme'] !== 'https') {
            throw new InvalidHostException('Host should be called via HTTPS!');
        }

        if (empty($urlParts['host'])) {
            throw new InvalidHostException('Domain must be specified.');
        }
    }

    /**
     * @param string $method
     * @param string $url
     * @param string[] $headers
     * @param string $body
     * @return Request
     */
    private function createHttpRequest($method, $url, $headers, $body)
    {
        return new Request($method, $url, $headers, $body);
    }

    /**
     * @param string $filename
     * @param string $url
     * @param string[] $headers
     */
    private function sendApiRequest($filename, $url, $headers)
    {
        $body = json_encode(['file' => $filename]);
        $request = $this->createHttpRequest('PUT', $url, $headers, $body);
        $client = new Client();
        $response = $client->send($request);
        if (!json_decode($response->getBody()) == 'OK') {
            throw new RequestFailedException();
        }
    }
}