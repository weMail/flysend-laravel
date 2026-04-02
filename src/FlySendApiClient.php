<?php

namespace FlySend\Laravel;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class FlySendApiClient
{
    protected Client $http;

    public function __construct(
        protected string $apiKey,
        protected string $endpoint = 'https://api.flysend.co',
    ) {
        $this->http = new Client([
            'base_uri' => rtrim($this->endpoint, '/'),
            'timeout' => 30,
        ]);
    }

    /**
     * Send an email via the FlySend API.
     *
     * @param  array  $payload
     * @return array
     *
     * @throws FlySendException
     */
    public function sendEmail(array $payload): array
    {
        try {
            $response = $this->http->post('/emails', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $message = 'FlySend API request failed';

            if ($e->hasResponse()) {
                $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                $message = $body['message'] ?? $body['error'] ?? $message;
            }

            throw new FlySendException($message, $e->getCode(), $e);
        }
    }
}
