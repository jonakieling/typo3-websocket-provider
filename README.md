# Security notice

Does not provide authentication, rate-limiting or user input validation.

# websocket_provider

Provider extension to run [Ratchet](https://github.com/ratchetphp/Ratchet) WebSocket servers within TYPO3.
This allows for example to access the Database or any TYPO3 Context.

The WebSocket server is run as a TYPO3 CLI command.

`composer req werkraum/websocket-provider`

`typo3cms websocket:start` (default port 18080)

## Your Server Logic

Implement the Ratchet interface `Ratchet\MessageComponentInterface` in your extension.
```php
class YourMessageComponent implements Ratchet\MessageComponentInterface
{
    // interface methods with your logic go here
}
```

Strictly speaking you only need to implement `Ratchet\ComponentInterface`

If your component implements `Ratchet\Wamp\WampServerInterface` it is automatically wrapped by the Ratchet
ServerProtocol component that handles the basics of the WAMP protocol.
See [the WAMP documentation](https://wamp-proto.org/wamp_latest_ietf.html#name-protocol-overview) for more information
on the protocol.

--- 

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

See [ext_conf_template.txt](ext_conf_template.txt) for all configuration options.

The server runs under the domain of a given TYPO3 site and only allows connections by the same domain.
The setting accepts a site identifier to keep things environment independent.

## Commands to start, stop and list running websocket servers
| Command           | Description                     |
|-------------------|---------------------------------|
| `websocket:list`    | Lists running websocket servers |
| `websocket:start`   | Starts the websocket server     |
| `websocket:stop`    | Stops the websocket server      |

## Server config

`ext-json`, `ext-pcntl` and `ext-posix` are required for the cli commands to check on the status of the server. 

You need a proxy pass for TLS websockets to work with Ratchet.

Nginx (adjust for your setup):
```
location /socket.io {
 proxy_pass http://localhost:18080;
 proxy_http_version 1.1;
 proxy_set_header Upgrade $http_upgrade;
 proxy_set_header Connection "Upgrade";
 proxy_set_header Host $host; 
}
```

The connection URL on the client then is `wss://domain.tld/socket.io`