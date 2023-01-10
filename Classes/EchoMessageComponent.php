<?php

/**
 * This file is part of the "websocket_provider" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Werkraum\WebsocketProvider;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

/**
 * Simple message handler that echoes any message send to it.
 */
class EchoMessageComponent implements MessageComponentInterface
{
    /**
     * @inheritDoc
     */
    function onOpen(ConnectionInterface $conn)
    {
        $conn->send('hi');
    }

    /**
     * @inheritDoc
     */
    function onClose(ConnectionInterface $conn)
    {
        $conn->send('bye');
    }

    /**
     * @inheritDoc
     */
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->send($e->getMessage());
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $from->send($msg);
    }
}