<?php

/**
 * This file is part of the "websocket_provider" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Werkraum\WebsocketProvider\Factory;

use Ratchet\ComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\SocketServer;
use RuntimeException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Werkraum\WebsocketProvider\Server\HttpServer;
use Werkraum\WebsocketProvider\Server\OriginCheck;
use Werkraum\WebsocketProvider\Utility\ProcessUtility;

class ServerFactory
{
    /**
     * @var string
     */
    protected string $host = '0.0.0.0';

    /**
     * @var int
     */
    protected int $port = 18080;

    /**
     * @var LoopInterface
     */
    protected LoopInterface $loop;

    /**
     * @var array
     */
    protected array $config;

    public function __construct()
    {
        $this->config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('websocket_provider');
        $this->loop = Loop::get();
    }

    /**
     * @param string $host
     */
    public function setHost(string $host): ServerFactory
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param int $port
     */
    public function setPort(int $port): ServerFactory
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param LoopInterface $loop
     */
    public function setLoop(LoopInterface $loop): ServerFactory
    {
        $this->loop = $loop;
        return $this;
    }

    /**
     * Creates a ratchet websocket server instance.
     *
     * Stack:
     * - TCP
     * - HTTP
     * - WebSocket
     * - Any message component
     *
     * @return IoServer
     */
    public function create(): IoServer
    {
        try {
            $component = GeneralUtility::makeInstance($this->config['app']['component']);
            if (!$component instanceof ComponentInterface) {
                throw new \RuntimeException('component must implement \Ratchet\ComponentInterface', 1673370821222);
            }
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(sprintf("websocket component %s", $e->getMessage()), 1673625570824);
        }

        $this->loop->addSignal(SIGINT, function () {
            unlink(ProcessUtility::infoDirectory() . getmypid() . '.pid');
            $this->loop->stop();
        });
        $this->loop->addSignal(SIGTERM, function () {
            unlink(ProcessUtility::infoDirectory() . getmypid() . '.pid');
            $this->loop->stop();
        });

        $socket = new SocketServer("$this->host:$this->port", [], $this->loop);

        if ($this->config['tls']['local_cert']) {
            echo "wss";
            $socket = new SocketServer(
                "tls://$this->host:$this->port",
                ['tls' => $this->config['tls']],
                $this->loop
            );
        }

        return new IoServer(
            new HttpServer(
                new OriginCheck(
                    // Laravel instead adds a Router which would make this multi-tenant
                    new WsServer(
                        $component
                    ),
                    $this->allowedOrigins()
                ),
                $this->config['server']['max_request_size_in_kb'] * 1024
            ),
            $socket,
            $this->loop
        );
    }

    /**
     * @param $siteIdentifier
     * @return Site
     */
    protected static function getSiteByIdentifier($siteIdentifier): Site
    {
        /** @var Site $site */
        $finder = GeneralUtility::makeInstance(SiteFinder::class);
        try {
            return $finder->getSiteByIdentifier($siteIdentifier);
        } catch (SiteNotFoundException $e) {
            throw new RuntimeException(sprintf('invalid site identifier %s set in websocket_provider settings', $siteIdentifier), 1673443276640);
        }
    }

    /**
     * Returns an array of hosts to allow connections from.
     *
     * You can use the prefix "site:" to allow the base of that TYPO3 site
     *
     * @return string[]
     */
    public function allowedOrigins(): array
    {
        $origins = explode(',', $this->config['server']['allowed_origins']);
        foreach ($origins as $index => $origin) {
            if (str_starts_with($origin, 'site:')) {
                $siteIdentifier = substr($origin, strlen('site:'));
                $site = self::getSiteByIdentifier($siteIdentifier);
                $origins[$index] = $site->getBase()->getHost();
            }
        }

        $origins = array_filter($origins);

        return $origins;
    }
}