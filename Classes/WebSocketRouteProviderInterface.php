<?php

namespace Werkraum\WebsocketProvider;

use React\EventLoop\LoopInterface;
use Symfony\Component\Routing\RouteCollection;

interface WebSocketRouteProviderInterface
{
    public function getRoutes(LoopInterface $loop): RouteCollection;
}