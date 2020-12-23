### Attention! Maybe some diff with IRL.
1. This package forked from "LaraComponents" to fit new Centrifugo v2.
2. Drop Redis support (v2 don't support it), see official [migration guide](https://centrifugal.github.io/centrifugo/misc/migrate/).
3. Update generateToken(user id, timestamp, info) method (v2 uses only jwt auth workflow).

## Introduction
Centrifuge broadcaster for laravel ^6

## Requirements

- PHP 7.2+
- Laravel 6+
- Centrifugo Server 2 or newer (see [here](https://github.com/centrifugal/centrifugo))

## Installation

Require this package with composer:

```bash
composer require emprove/centrifuge-broadcaster
```

Open your config/app.php and add the following to the providers array:

```php
'providers' => [
    // ...
    Emprove\Centrifugo\CentrifugoServiceProvider::class,

    // And uncomment BroadcastServiceProvider
    App\Providers\BroadcastServiceProvider::class,
],
```

Open your config/broadcasting.php and add the following to it:

```php
'connections' => [
    'centrifuge' => [
        'driver'       => 'centrifuge',
        'url'          => env('CENTRIFUGE_URL', 'http://127.0.0.1:8000'),
        'token_ttl'    => env('CENTRIFUGE_TOKEN_TTL', 3600),
        'token_issuer' => env('APP_URL', 'default'),
        'secret'       => env('CENTRIFUGE_SECRET', null),
        'api_key'      => env('CENTRIFUGE_API_KEY', null),
        'ssl_key'      => env('CENTRIFUGE_SSL_KEY', null),
        'verify'       => env('CENTRIFUGE_VERIFY', false),
    ],
    // ...
],
```

You can also add a configuration to your .env file:

```
CENTRIFUGE_API_KEY=very-long-secret-api-key
CENTRIFUGE_SECRET=very-long-secret-key
CENTRIFUGE_URL=http://localhost:8000
CENTRIFUGE_SSL_KEY=/etc/ssl/some.pem
CENTRIFUGE_TOKEN_TTL=3600 # seconds
CENTRIFUGE_VERIFY=false
```

Do not forget to install the broadcast driver

```
BROADCAST_DRIVER=centrifuge
```

## Basic Usage

To configure the Centrifugo server, read the [official documentation](https://fzambia.gitbooks.io/centrifugal/content)

For broadcasting events, see the [official documentation of laravel](https://laravel.com/docs/5.3/broadcasting)

A simple example of using the client:

```php
<?php

namespace App\Http\Controllers;

use Emprove\Centrifugo\Contracts\Centrifugo;

class ExampleController extends Controller
{
    public function home(Centrifugo $centrifuge)
    {
        // Send message into channel
        $centrifuge->publish('channel-name', [
            'key' => 'value'
        ]);

        // Generate api sign
        $apiSign = $centrifuge->generateApiSign('data');

        // ...
    }
}
```

### Available methods

| Name | Description |
|------|-------------|
| publish(string $channel, array $data, string $client = null) | Send message into channel. |
| broadcast(array $channels, array $data, string $client = null) | Send message into multiple channel. |
| presence(string $channel) | Get channel presence information (all clients currently subscribed on this channel). |
| history(string $channel) | Get channel history information (list of last messages sent into channel). |
| unsubscribe(string $user_id, string $channel = null) | Unsubscribe user from channel. |
| disconnect(string $user_id) | Disconnect user by its ID. |
| channels() | Get channels information (list of currently active channels). |
| stats() | Get stats information about running server nodes. |
| generateToken(string $userId) | Generate JWT token for client. |
| generateApiSign(string $data) | Generate api sign. |

## License

The MIT License (MIT). Please see [License File](https://github.com/LaraComponents/centrifuge-broadcaster/blob/master/LICENSE) for more information.
