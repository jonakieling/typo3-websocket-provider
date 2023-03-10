<?php

/**
 * This file is part of the "websocket_provider" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Werkraum\WebsocketProvider\Factory;

use Ratchet\ComponentInterface;
use Ratchet\Http\Router;
use Ratchet\Server\IoServer;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\SocketServer;
use RuntimeException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Routing\RouteCollection;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Werkraum\WebsocketProvider\Interfaces\ConfigureLoopInterface;
use Werkraum\WebsocketProvider\Interfaces\CustomRouteInterface;
use Werkraum\WebsocketProvider\Server\Authentication;
use Werkraum\WebsocketProvider\Server\HttpServer;
use Werkraum\WebsocketProvider\Server\Limiter;
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

    /**
     * @var iterable
     */
    protected $messageComponents;

    /**
     * @param iterable $messageComponents
     */
    public function __construct(iterable $messageComponents)
    {
        $this->config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('websocket_provider');
        $this->loop = Loop::get();
        $this->messageComponents = $messageComponents;
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
     * - client origin check
     * - rate/connection limiter
     * - TYPO3 authentication (using existing FE or BE session)
     * - WebSocket
     * - Any message component
     *
     * @return IoServer
     */
    public function create(): IoServer
    {
        $this->loop->addSignal(SIGINT, function () {
            unlink(ProcessUtility::infoDirectory() . getmypid() . '.pid');
            $this->loop->stop();
        });
        $this->loop->addSignal(SIGTERM, function () {
            unlink(ProcessUtility::infoDirectory() . getmypid() . '.pid');
            $this->loop->stop();
        });

        if ($this->config['tls']['local_cert']) {
            $socket = new SocketServer(
                "tls://$this->host:$this->port",
                ['tls' => $this->config['tls']],
                $this->loop
            );
        } else {
            $socket = new SocketServer("$this->host:$this->port", [], $this->loop);
        }

        /**
         * Build the server stack
         */

        $routes = new RouteCollection();

        foreach ($this->messageComponents as $component) {
            if ($component instanceof ComponentInterface) {
                if ($component instanceof ConfigureLoopInterface) {
                    $component->configureLoop($this->loop);
                }
                if ($component instanceof CustomRouteInterface) {
                    $path = $component->getPath();
                    if (strpos($path, '/') !== 0) {
                        $path = '/' . $path;
                    }
                } else {
                    $path = '/' . str_replace("\\", '_', get_class($component));
                }
                if (strpos($path, $this->config['server']['route_prefix']) !== 0) {
                    $path = $this->config['server']['route_prefix'] . $path;
                }
                $routes->add($path, WebSocketRouteFactory::createRoute($path, $component));
            }
        }
        $context = new RequestContext;
        $matcher = new UrlMatcher($routes, $context);
        $router = new Router($matcher);

        $auth = new Authentication($router);

        $limiter = (new Limiter($auth))
            ->setMaxConnections($this->config['server']['max_connections'])
            ->setMaxConnectionsPerAddress($this->config['server']['max_connections_per_address'])
            ->setMaxMessagesPerSecond($this->config['server']['max_messages_per_second'])
            ->setMaxConnectionsPerAddress($this->config['server']['max_connections_per_address_per_second']);

        $originCheck = new OriginCheck($limiter, $this->allowedOrigins());

        $httpServer = new HttpServer(
            $originCheck,
            $this->config['server']['max_request_size_in_kb'] * 1024
        );

        $ioServer = new IoServer(
            $httpServer,
            $socket,
            $this->loop
        );

        return $ioServer;
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