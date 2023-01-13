<?php

namespace Werkraum\WebsocketProvider\Server;

use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\CloseResponseTrait;

class OriginCheck extends \Ratchet\Http\OriginCheck
{
    use CloseResponseTrait;

    public function onOpen(ConnectionInterface $connection, RequestInterface $request = null)
    {
        if ($request->hasHeader('Origin')) {
            $this->verifyOrigin($connection, $request);
        }

        return $this->_component->onOpen($connection, $request);
    }

    protected function verifyOrigin(ConnectionInterface $connection, RequestInterface $request)
    {
        $header = (string) $request->getHeader('Origin')[0];
        $origin = parse_url($header, PHP_URL_HOST) ?: $header;

        if (! empty($this->allowedOrigins) && ! in_array($origin, $this->allowedOrigins)) {
            return $this->close($connection, 403);
        }
    }
}