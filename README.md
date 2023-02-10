# websocket_provider

Provides basic setup to run [Ratchet](https://github.com/ratchetphp/Ratchet) WebSocket servers within TYPO3.

Currently only for *nix Systems

`composer req werkraum/websocket-provider`

`typo3cms websocket:start`

## Routes and Components

### Components

Implement the Ratchet interface `Ratchet\MessageComponentInterface` in your extension.
This is going to be the entry point for your logic.
```php
class YourMessageComponent implements Ratchet\MessageComponentInterface
{
    // interface methods with your logic go here
}
```

Mark your component as public to make use of the Symfony dependency injection within it.
Tag your component with `websocket.component` to register it with the WebSocket provider.

_Configuration/Services.yaml_
```yaml
# default service setup goes here

services:
  Vendor\Extension\WebSocket\YourMessageComponent:
    public: true
    tags: ['websocket.component']
```

### Routes

Symfony Routes are used to provide multi-tenancy.

You may let your component implement the interface [CustomRouteInterface](Classes%2FInterfaces%2FCustomRouteInterface.php).
Return your custom route path with a leading slash (e.g. `/socket.io/my_component`).

> The default route for `Vendor\Extension\MyComponent` is `/Vendor_Extension_MyComponent`

> Be aware that the route of your WebSockets may be prefixed by whatever you have set for your webserver config. In my
> case this is often /socket.io. You can set this server-wide prefix in the settings. The prefix is then prepended to
> every component route, even third-party ones using this extension.

### The Loop

In case you need periodic timer or want to handle signals you can implement the interface
[ConfigureLoopInterface](Classes%2FInterfaces%2FConfigureLoopInterface.php).
You'll get the loop as parameter. The signals SIGTERM and SIGINT are already registered to shut down the server.

## Configuration

See [ext_conf_template.txt](ext_conf_template.txt) for all configuration options.

## Commands
| Command            | Description                     |
|--------------------|---------------------------------|
| `websocket:list`   | Lists running websocket servers |
| `websocket:routes` | Lists all configured routes     |
| `websocket:start`  | Starts the websocket server     |
| `websocket:stop`   | Stops the websocket server      |

## Demo

Ratchet ships a simple echo component. You can register it via Symfony DI.

any _Configuration/Services.yaml_
```yaml
Ratchet\Server\EchoServer:
  public: true
  tags: ['websocket.component']
```

## Security

### Authentication

The current frontend and backend user sessions are added to the connection as UserAspects.

Connections are not rejected when not authenticated.

### Rate limiting

A basic limiter is shipped and can be configured in the settings. More sophisticated rate limiting should probably be
done by your webserver.

## Server config

`ext-json`, `ext-pcntl` and `ext-posix` are required for the cli commands to check on the status of the server. 

### NGINX

Laravel has some good documentation on how to setup NGINX for WebSockets.

https://beyondco.de/docs/laravel-websockets/basic-usage/ssl#usage-with-a-reverse-proxy-like-nginx

## Thanks

Lots of ideas and inspiration taken from https://freek.dev/1228-introducing-laravel-websockets-an-easy-to-use-websocket-server-implemented-in-php