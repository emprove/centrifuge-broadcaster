<?php

namespace Emprove\Centrifugo;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Emprove\Centrifugo\Contracts\Centrifugo as CentrifugoContract;

class CentrifugoBroadcaster extends Broadcaster
{
    /**
     * The Centrifugo SDK instance.
     *
     * @var CentrifugoContract
     */
    protected $centrifugo;

    /**
     * Create a new broadcaster instance.
     *
     * @param CentrifugoContract $centrifugo
     */
    public function __construct(CentrifugoContract $centrifugo)
    {
        $this->centrifugo = $centrifugo;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function auth($request)
    {
        if ($request->user()) {
            $client   = $request->get('client', '');
            $channels = $request->get('channels', []);
            $channels = is_array($channels) ? $channels : [$channels];

            $response = [];
            $info     = json_encode([]);

            foreach ($channels as $channel) {
                $channelName = (substr($channel, 0, 1) === '$')
                    ? substr($channel, 1)
                    : $channel;

                try {
                    $result = $this->verifyUserCanAccessChannel($request, $channelName);
                } catch (HttpException $e) {
                    $result = false;
                }

                $response[$channel] = $result ? [
                    'info' => $info,
                ] : [
                    'status' => 403,
                ];
            }

            return response()->json($response);
        } else {
            throw new HttpException(401);
        }
    }

    /**
     * Return the valid authentication response.
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed                    $result
     *
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        return $result;
    }

    /**
     * Broadcast the given event.
     *
     * @param array  $channels
     * @param string $event
     * @param array  $payload
     *
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $payload['event'] = $event;

        $socket = null;

        if (array_key_exists('socket', $payload)) {
            $socket = $payload['socket'];
            unset($payload['socket']);
        }

        $response = $this->centrifugo->broadcast($this->formatChannels($channels), $payload, $socket);

        if (is_array($response) && empty($response) && !isset($response['error'])) {
            return;
        }

        if (isset($response['error'])) {
            throw new BroadcastException(
                $response['error'] instanceof Exception
                    ? $response['error']->getMessage()
                    : $response['error']
            );
        }
    }

    /**
     * Get the Centrifugo instance.
     *
     * @return CentrifugoContract
     */
    public function getCentrifugo()
    {
        return $this->centrifugo;
    }
}
