<?php

/**
 * This file is part of the "websocket_provider" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Werkraum\WebsocketProvider\Factory;

use Exception;
use Ratchet\ComponentInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Http\OriginCheck;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use RuntimeException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ServerFactory
{
    /**
     * Constructs a ratchet websocket server instance that can be run directly.
     *
     * Stack:
     * - TCP/IP or TLS
     * - HTTP/S
     * - WebSocket
     * - Any message component
     *
     * @return IoServer
     */
    public static function create(): IoServer
    {
        $config = self::getExtensionConfig();
        $component = GeneralUtility::makeInstance($config['component']);
        if (!$component instanceof ComponentInterface) {
            throw new RuntimeException('component must implement \Ratchet\ComponentInterface', 1673370821222);
        }
        $ip = $config['ip'];
        $port = $config['port'];
        $site = self::getSiteByIdentifier($config['siteIdentifier']);

        $loop = Loop::get();

        if ($site->getBase()->getScheme() === 'https') {
            $socket = new SocketServer("tls://$ip:$port", [
                'tls' => [
                    'local_cert' => $config['tlsCert'],
                    'local_pk' => $config['tlsKey'],
                    'verify_peer' => false,
                    'allow_self_signed' => $config['allowSelfSigned']
                ],
            ], $loop);
        } else {
            $socket = new SocketServer("$ip:$port", [], $loop);
        }

        $socket->on('error', function (Exception $e) {
            echo 'Error' . $e->getMessage() . PHP_EOL;
        });

        return new IoServer(
            new HttpServer(
                new OriginCheck(
                    new WsServer(
                        $component
                    ),
                    ['localhost', $site->getBase()->getHost()]
                )
            ),
            $socket,
            $loop
        );
    }

    /**
     * @return mixed
     */
    protected static function getExtensionConfig()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get('websocket_provider');
    }

    /**
     * @param $siteIdentifier
     * @return mixed
     */
    protected static function getSiteByIdentifier($siteIdentifier)
    {
        /** @var Site $site */
        $finder = GeneralUtility::makeInstance(SiteFinder::class);
        try {
            return $finder->getSiteByIdentifier($siteIdentifier);
        } catch (SiteNotFoundException $e) {
            throw new RuntimeException(sprintf('invalid site identifier %s set in websocket_provider settings', $siteIdentifier), 1673443276640);
        }
    }
}