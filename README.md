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
The ID of the connection is added as well.
```php
$conn->feUser
$conn->beUser
```

Connections are not rejected when not authenticated. Additionally the authentication is only checked on opening a
connection.

### Rate limiting

A basic limiter is shipped and can be configured in the settings. More sophisticated rate limiting should probably be
done by your webserver.

## Server config

`ext-json`, `ext-pcntl` and `ext-posix` are required for the cli commands to check on the status of the server. 

### NGINX

Laravel has some good documentation on how to setup NGINX for WebSockets.

https://beyondco.de/docs/laravel-websockets/basic-usage/ssl#usage-with-a-reverse-proxy-like-nginx

### DDEV

Open the port. Either via DDEV config [`web_extra_exposed_ports`](https://ddev.readthedocs.io/en/stable/users/configuration/config/#web_extra_exposed_ports) or a custom docker-compose file.

_docker-compose.websockets.yaml_
```yaml
version: '3.6'
services:
  web:
    ports:
      - "18080"
```

Port and the URL part `/socket.io` can be adjusted to your needs. I recommend setting the extension setting
`route_prefix` to whatever you set as the URL part in your websocket config.

Below is the proxy pass config that needs to be added to the webserver config. This uses a custom URL part to
distinguish between WebSocket and regular HTTP requests. This integrates well with existing webserver configuration for
TYPO3. The Laravel documentation has a Nginx setup without the URL part.

> Remember to remove the `#ddev-generated` line in the webserver config otherwise your changes will be overridden after
> restarting DDEV.

#### Apache

```
<Location "/socket.io">
    LoadModule proxy_wstunnel_module /usr/lib/apache2/modules/mod_proxy_wstunnel.so
    ProxyPass "ws://localhost:18080/socket.io"
    ProxyPassReverse "ws://localhost:18080/socket.io"
    ProxyPreserveHost On
    RequestHeader set Upgrade "websocket"
    RequestHeader set Connection "Upgrade"
</Location>
```

#### NGINX

```
location /socket.io {
    proxy_pass http://localhost:18080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
}
```

## Thanks

Lots of ideas and inspiration taken from https://freek.dev/1228-introducing-laravel-websockets-an-easy-to-use-websocket-server-implemented-in-php