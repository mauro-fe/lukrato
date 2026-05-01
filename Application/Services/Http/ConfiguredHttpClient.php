<?php

declare(strict_types=1);

namespace Application\Services\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ConfiguredHttpClient implements ClientInterface
{
    private ClientInterface $client;

    public function __construct(array $config = [], ?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client($config);
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->client->send($request, $options);
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return $this->client->sendAsync($request, $options);
    }

    public function request(string $method, $uri, array $options = []): ResponseInterface
    {
        return $this->client->request($method, $uri, $options);
    }

    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface
    {
        return $this->client->requestAsync($method, $uri, $options);
    }

    public function getConfig(?string $option = null): mixed
    {
        return $this->client->getConfig($option);
    }

    public function get($uri, array $options = []): ResponseInterface
    {
        return $this->request('GET', $uri, $options);
    }

    public function post($uri, array $options = []): ResponseInterface
    {
        return $this->request('POST', $uri, $options);
    }

    public function put($uri, array $options = []): ResponseInterface
    {
        return $this->request('PUT', $uri, $options);
    }

    public function patch($uri, array $options = []): ResponseInterface
    {
        return $this->request('PATCH', $uri, $options);
    }

    public function delete($uri, array $options = []): ResponseInterface
    {
        return $this->request('DELETE', $uri, $options);
    }
}
