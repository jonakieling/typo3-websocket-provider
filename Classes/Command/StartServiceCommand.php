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
use TYPO3\CMS\Core\Core\Environment;

class StartServiceCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cmd = 'nohup '. PHP_BINARY .' '.Environment::getProjectPath().'/vendor/bin/typo3cms websocket:start';
        exec($cmd, $out);
        $output->writeln($out);

        return Command::SUCCESS;
    }
}