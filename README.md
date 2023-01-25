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

## Routes and Components

Symfony Routes are used to provide multi-tenancy. Your WebSocket service needs at least one component and a route
provider which provides a route to your component.

### Components

Implement the Ratchet interface `Ratchet\MessageComponentInterface` in your extension.
This is going to be the entry point for your logic.
```php
class YourMessageComponent implements Ratchet\MessageComponentInterface
{
    // interface methods with your logic go here
}
```

Mark you component as public to make use of the Symfony dependency injection within it.

_Configuration/Services.yaml_
```yaml
# default service setup goes here

services:
  Vendor\Extension\WebSocket\YourMessageComponent:
    public: true
```

### Routes

Implement the interface [`WebSocketRouteProviderInterface`](Classes%2FWebSocketRouteProviderInterface.php). The only
method returns a Symfony RouteCollection. Each Route must have a WsServer as controller.
You can use [WebSocketRouteFactory](Classes%2FFactory%2FWebSocketRouteFactory.php) to create WsServer Routes easily.

> Be aware that the route of your WebSockets may be prefixed by whatever you have set for your Nginx config. In my case
> this is often /socket.io to dealt with TLS.

## Configuration

See [ext_conf_template.txt](ext_conf_template.txt) for all configuration options.

## Commands
| Command            | Description                     |
|--------------------|---------------------------------|
| `websocket:list`   | Lists running websocket servers |
| `websocket:routes` | Lists all configured routes     |
| `websocket:start`  | Starts the websocket server     |
| `websocket:stop`   | Stops the websocket server      |

## Server config

`ext-json`, `ext-pcntl` and `ext-posix` are required for the cli commands to check on the status of the server. 

### NGINX

Laravel has some good documentation on how to setup NGINX for WebSockets.

https://beyondco.de/docs/laravel-websockets/basic-usage/ssl#usage-with-a-reverse-proxy-like-nginx

## Thanks

Lots of ideas and inspiration taken from https://freek.dev/1228-introducing-laravel-websockets-an-easy-to-use-websocket-server-implemented-in-php