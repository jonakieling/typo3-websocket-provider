<?php

namespace Werkraum\WebsocketProvider\Factory;

use Ratchet\WebSocket\MessageComponentInterface;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Routing\Route;

/**
 * Helper to create WebSocket routes. Intended to help with services implementing WebSocketRouteInterface.
 */
class WebSocketRouteFactory
{
    /**
     * Wraps components with a WsServer and returns
     *
     * @param string $uri
     * @param MessageComponentInterface $component
     * @return Route
     */
    public function createRoute(string $uri, $component)
    {
        if ($component instanceof MessageComponentInterface) {
            throw new \RuntimeException('route controller must implement MessageComponentInterface', 1674660735943);
        }
        $action = new WsServer($component);

        return new Route($uri, ['_controller' => $action], [], [], null, [], []);
    }
}