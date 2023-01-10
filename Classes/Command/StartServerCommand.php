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
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Werkraum\WebsocketProvider\Factory\ServerFactory;
use Werkraum\WebsocketProvider\Utility\ProcessUtility;

class StartServerCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $server = ServerFactory::create();
        $server->loop->addSignal(SIGINT, function () use ($server, $output) {
            unlink(ProcessUtility::infoDirectory() . getmypid() . '.pid');
            $server->loop->stop();
            $output->writeln('');
        });
        $server->loop->addSignal(SIGTERM, function () use ($server, $output) {
            unlink(ProcessUtility::infoDirectory() . getmypid() . '.pid');
            $server->loop->stop();
            $output->writeln('');
        });

        $config = GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get('websocket_provider');

        $output->writeln(
            sprintf(
                '<info>%s running on %s with pid %d</info>',
                $config['component'],
                $server->socket->getAddress(),
                getmypid()
            )
        );

        ProcessUtility::saveInfoFile(getmypid(), $server->socket->getAddress(), $config['component']);

        $server->run();

        return Command::SUCCESS;
    }
}