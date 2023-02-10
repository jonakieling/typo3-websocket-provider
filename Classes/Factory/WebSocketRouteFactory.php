<?php

namespace Werkraum\WebsocketProvider\Factory;

use Ratchet\ComponentInterface;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Routing\Route;

/**
 * Helper to create WebSocket routes. Intended to help with services implementing WebSocketRouteInterface.
 */
class WebSocketRouteFactory
{
    /**
     * Wraps components with a WsServer and returns a simple route
     *
     * @param string $uri
     * @param ComponentInterface $component
     * @return Route
     */
    public static function createRoute(string $uri, $component)
    {
        if (!$component instanceof ComponentInterface) {
            throw new \RuntimeException('route controller must at least implement ComponentInterface', 1674660735943);
        }
        $action = new WsServer($component);

        return new Route($uri, ['_controller' => $action], [], [], null, [], []);
    }
}