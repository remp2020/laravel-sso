<?php

namespace Remp\LaravelSso\Contracts\Remp;

use Remp\LaravelSso\Contracts\SsoContract;
use Remp\LaravelSso\Contracts\SsoException;
use Remp\LaravelSso\Contracts\SsoExpiredException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Sso implements SsoContract
{
    const ENDPOINT_INTROSPECT = 'api/auth/introspect';

    const ENDPOINT_REFRESH = 'api/auth/refresh';

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function introspect($token): array
    {
        try {
            $response = $this->client->request('GET', self::ENDPOINT_INTROSPECT, [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ]
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $contents = $response->getBody()->getContents();
            $body = \GuzzleHttp\json_decode($contents);
            switch ($response->getStatusCode()) {
                case 400:
                case 401:
                    $e = new SsoExpiredException();
                    $e->redirect = $body->redirect;
                    throw $e;
                default:
                    throw new SsoException($contents);
            }
        }

        $user = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
        return $user;
    }

    public function refresh($token): array
    {
        try {
            $response = $this->client->request('POST', self::ENDPOINT_REFRESH, [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ]
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $contents = $response->getBody()->getContents();
            $body = \GuzzleHttp\json_decode($contents);
            switch ($response->getStatusCode()) {
                case 400:
                case 401:
                    $e = new SsoExpiredException();
                    $e->redirect = $body->redirect;
                    throw $e;
                default:
                    throw new Nette\Security\AuthenticationException($contents);
            }
        }

        $tokenResponse = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
        return $tokenResponse;
    }
}