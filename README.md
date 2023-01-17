# websocket_provider

Provides basic setup to run [Ratchet](https://github.com/ratchetphp/Ratchet) WebSocket servers within TYPO3.

`composer req werkraum/websocket-provider`

`typo3cms websocket:start`

## Security

### Authentication

The current frontend and backend user sessions are added to the connection as UserAspects.

Connections are not rejected when not authenticated.

### Rate limiting

A basic limiter is shipped and can be configured in the settings.

## Implementing Handlers

Implement the Ratchet interface `Ratchet\MessageComponentInterface` in your extension. `Ratchet\ComponentInterface` is
sufficient if you do not need message handling.
```php
class YourMessageComponent implements Ratchet\MessageComponentInterface
{
    // interface methods with your logic go here
}
```

Set your message component in the extension settings.

_ext_localconf.php_
```php
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['websocket_provider']['component'] = Vendor\Extensions\WebSocket\YourMessageComponent::class
```

Mark you component as public to make use of the Symfony dependency injection

_Configuration/Services.yaml_
```yaml
# default service setup goes here

services:
  Vendor\Extension\WebSocket\YourMessageComponent:
    public: true
```

## Configuration

See [ext_conf_template.txt](ext_conf_template.txt) for all configuration options.

## Commands
| Command           | Description                     |
|-------------------|---------------------------------|
| `websocket:list`    | Lists running websocket servers |
| `websocket:start`   | Starts the websocket server     |
| `websocket:stop`    | Stops the websocket server      |

## Server config

`ext-json`, `ext-pcntl` and `ext-posix` are required for the cli commands to check on the status of the server. 

### NGINX

Laravel has some good documentation on how to setup NGINX for WebSockets.

https://beyondco.de/docs/laravel-websockets/basic-usage/ssl#usage-with-a-reverse-proxy-like-nginx
