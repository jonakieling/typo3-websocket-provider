<?php

namespace Werkraum\WebsocketProvider;

use Symfony\Component\Routing\RouteCollection;

interface WebSocketRouteProviderInterface
{
    public function getRoutes(): RouteCollection;
}