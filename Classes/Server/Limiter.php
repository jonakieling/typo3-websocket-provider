<?php

namespace Werkraum\WebsocketProvider\Server;

use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\CloseResponseTrait;
use Ratchet\Http\HttpServerInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\WebSocket\WsServerInterface;

class Limiter implements HttpServerInterface
{
    use CloseResponseTrait;

    /**
     * @var MessageComponentInterface
     */
    protected MessageComponentInterface $component;

    /**
     * @var int
     */
    protected int $maxConnections = 10000;

    /**
     * @var int
     */
    protected int $maxConnectionsPerAddress = 10;

    /**
     * @var int
     */
    protected int $maxMessagesPerSecond = 10;

    /**
     * @var int
     */
    protected int $maxConnectionsPerSecond = 10;

    /**
     * @var int
     */
    protected int $connections = 0;

    /**
     * address: connection count
     *
     * @var array
     */
    protected array $connectionsPerAddress = [];

    /**
     * 0: timestamp
     * 1: count
     *
     * @var array
     */
    protected array $messageCounters = [];

    /**
     * 0: timestamp
     * 1: count
     *
     * @var array
     */
    protected array $connectionCounters = [];

    public function __construct(MessageComponentInterface $component)
    {
        $this->component = $component;
    }

    function onClose(ConnectionInterface $conn)
    {
        $this->connections--;
        if ($this->connections < 0) {
            $this->connections = 0;
        }

        $this->connectionsPerAddress[$conn->remoteAddress]--;
        if ($this->connectionsPerAddress[$conn->remoteAddress] <= 0) {
            unset($this->connectionsPerAddress[$conn->remoteAddress]);
        }

        $this->component->onClose($conn);
    }

    function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->component->onError($conn, $e);
    }

    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        $this->connections++;

        if ($this->connections > $this->maxConnections) {
            return $this->close($conn, 429);
        }

        $this->connectionsPerAddress[$conn->remoteAddress]++;

        if ($this->connectionsPerAddress[$conn->remoteAddress] > $this->maxConnectionsPerAddress) {
            return $this->close($conn, 429);
        }


        $now = time();
        $this->connectionCounters[$conn->remoteAddress][$now]++;
        $this->connectionCounters[$conn->remoteAddress] = array_slice($this->connectionCounters[$conn->remoteAddress], -10, null, true);

        if ($this->connectionCounters[$conn->remoteAddress][$now] > $this->maxConnectionsPerSecond) {
            return $this->close($conn, 429);
        }

        if ($this->component instanceof HttpServerInterface) {
            $this->component->onOpen($conn, $request);
        } else {
            $this->component->onOpen($conn);
        }
    }

    function onMessage(ConnectionInterface $from, $msg)
    {
        $now = time();
        $this->messageCounters[$from->remoteAddress][$now]++;
        $this->messageCounters[$from->remoteAddress] = array_slice($this->messageCounters[$from->remoteAddress], -10, null, true);

        if ($this->messageCounters[$from->remoteAddress][$now] > $this->maxMessagesPerSecond) {
            return $this->close($from, 429);
        }

        $this->component->onMessage($from, $msg);
    }

    function getSubProtocols()
    {
        return ($this->component instanceof WsServerInterface ? $this->component->getSubProtocols() : []);
    }

    /**
     * @param int $maxConnections
     * @return Limiter
     */
    public function setMaxConnections(int $maxConnections): Limiter
    {
        $this->maxConnections = $maxConnections;
        return $this;
    }

    /**
     * @param int $maxConnectionsPerAddress
     * @return Limiter
     */
    public function setMaxConnectionsPerAddress(int $maxConnectionsPerAddress): Limiter
    {
        $this->maxConnectionsPerAddress = $maxConnectionsPerAddress;
        return $this;
    }

    /**
     * @param int $maxMessagesPerSecond
     * @return Limiter
     */
    public function setMaxMessagesPerSecond(int $maxMessagesPerSecond): Limiter
    {
        $this->maxMessagesPerSecond = $maxMessagesPerSecond;
        return $this;
    }
}