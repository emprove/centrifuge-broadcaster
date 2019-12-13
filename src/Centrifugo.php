<?php

namespace Emprove\Centrifugo;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Emprove\Centrifugo\Contracts\Centrifugo as CentrifugoContract;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class Centrifugo implements CentrifugoContract
{
    /**
     * Centrifugo endpoint to deal with HTTP API. Default is "/api".
     */
    const API_PATH = '/api';

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @var array
     */
    protected $config;

    /**
     * Create a new Centrifugo instance.
     *
     * @param array  $config
     * @param Client $httpClient
     */
    public function __construct(array $config = [], Client $httpClient = null)
    {
        $this->httpClient = $httpClient;
        $this->config     = $config;
    }

    /**
     * Send message into channel.
     *
     * @param string $channel
     * @param array  $data
     * @param string $client
     *
     * @return mixed
     */
    public function publish($channel, array $data, $client = null)
    {
        $params = ['channel' => $channel, 'data' => $data];

        if (!is_null($client)) {
            $params['client'] = $client;
        }

        return $this->send('publish', $params);
    }

    /**
     * Send message to centrifugo server.
     *
     * @param string $method
     * @param array  $params
     *
     * @return mixed
     */
    protected function send($method, array $params = [])
    {
        try {
            $result = $this->httpSend($method, $params);
        } catch (Exception $e) {
            $result = [
                'method' => $method,
                'error'  => $e->getMessage(),
                'body'   => $params,
            ];
        }

        return $result;
    }

    /**
     * Send message to centrifugo server from http client.
     *
     * @param string $method
     * @param array  $params
     *
     * @return mixed
     */
    protected function httpSend($method, array $params = [])
    {
        $json = json_encode(['method' => $method, 'params' => $params]);

        $headers = [
            'Content-type'  => 'application/json',
            'Authorization' => 'apikey ' . collect($this->config)->get('api_key'),
        ];

        try {
            $url = parse_url($this->prepareUrl());

            $config = collect([
                'headers'     => $headers,
                'body'        => $json,
                'http_errors' => false,
            ]);

            if ($url['scheme'] == 'https') {
                $config->put('verify', collect($this->config)->get('verify', false));

                if (collect($this->config)->get('ssl_key')) {
                    $config->put('ssl_key', collect($this->config)->get('ssl_key'));
                }
            }

            $response = $this->httpClient->post($this->prepareUrl(), $config->toArray());

            $finally = json_decode((string)$response->getBody(), true);
        } catch (ClientException $e) {
            throw $e;
        }

        return $finally;
    }

    /**
     * Prepare URL to send the http request.
     *
     * @return string
     */
    protected function prepareUrl()
    {
        $address = rtrim($this->config['url'], '/');

        if (substr_compare($address, static::API_PATH, -strlen(static::API_PATH)) !== 0) {
            $address .= static::API_PATH;
        }

        return $address;
    }

    /**
     * Send message into multiple channel.
     *
     * @param array  $channels
     * @param array  $data
     * @param string $client
     *
     * @return mixed
     */
    public function broadcast(array $channels, array $data, $client = null)
    {
        $params = ['channels' => $channels, 'data' => $data];

        if (!is_null($client)) {
            $params['client'] = $client;
        }

        return $this->send('broadcast', $params);
    }

    /**
     * Get channel presence information (all clients currently subscribed on this channel).
     *
     * @param string $channel
     *
     * @return mixed
     */
    public function presence($channel)
    {
        return $this->send('presence', ['channel' => $channel]);
    }

    /**
     * Get channel history information (list of last messages sent into channel).
     *
     * @param string $channel
     *
     * @return mixed
     */
    public function history($channel)
    {
        return $this->send('history', ['channel' => $channel]);
    }

    /**
     * Unsubscribe user from channel.
     *
     * @param string $user_id
     * @param string $channel
     *
     * @return mixed
     */
    public function unsubscribe($user_id, $channel = null)
    {
        $params = ['user' => (string)$user_id];

        if (!is_null($channel)) {
            $params['channel'] = $channel;
        }

        return $this->send('unsubscribe', $params);
    }

    /**
     * Disconnect user by its ID.
     *
     * @param string $user_id
     *
     * @return mixed
     */
    public function disconnect($user_id)
    {
        return $this->send('disconnect', ['user' => (string)$user_id]);
    }

    /**
     * Get channels information (list of currently active channels).
     *
     * @return mixed
     */
    public function channels()
    {
        return $this->send('channels');
    }

    /**
     * Get stats information about running server nodes.
     *
     * @return mixed
     */
    public function stats()
    {
        return $this->send('stats');
    }

    /**
     * Generate api sign.
     *
     * @param string $data
     *
     * @return string
     */
    public function generateApiSign($data)
    {
        $ctx = hash_init('sha256', HASH_HMAC, $this->getSecret());
        hash_update($ctx, (string)$data);

        return hash_final($ctx);
    }

    /**
     * Get secret key.
     *
     * @return string
     */
    protected function getSecret()
    {
        return $this->config['secret'];
    }

    /**
     * Generate JWT token for client.
     *
     * @param string $userId
     *
     * @return string
     */
    public function generateToken(string $userId)
    {
        $signer = new Sha256();

        $token = (new Builder())->setIssuer($this->config['token_issuer'])
            ->setExpiration(now()->getTimestamp() + $this->config['token_ttl'])
            ->set('sub', $userId)
            ->sign($signer, $this->config['secret'])
            ->getToken();

        return $token;
    }
}
