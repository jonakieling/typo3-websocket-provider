<?php

/**
 * This file is part of the "websocket_provider" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Werkraum\WebsocketProvider\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Route;
use Werkraum\WebsocketProvider\WebSocketRouteProviderInterface;

class ListRoutesCommand extends Command
{

    /**
     * @var iterable
     */
    protected $webSocketRouterProvider;

    /**
     * @param iterable $webSocketRouterProvider
     */
    public function __construct(iterable $webSocketRouterProvider)
    {
        parent::__construct();
        $this->webSocketRouterProvider = $webSocketRouterProvider;
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var WebSocketRouteProviderInterface $routeProvider */
        foreach ($this->webSocketRouterProvider as $routeProvider) {
            /** @var Route $route */
            foreach ($routeProvider->getRoutes() as $name => $route) {
                $output->writeln("<info>{$route->getPath()}</info> $name");
            }
        }
        return Command::SUCCESS;
    }
}