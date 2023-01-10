<?php

/**
 * This file is part of the "websocket_provider" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Werkraum\WebsocketProvider\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Werkraum\WebsocketProvider\Utility\ProcessUtility;

class StopServerCommand extends Command
{

    protected function configure()
    {
        $this->addArgument('pid', InputArgument::OPTIONAL, 'pid of the server process');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $requestedPid = $input->hasArgument('pid') ? $input->getArgument('pid') : null;
        $finder = new Finder();
        $finder->files()->in(ProcessUtility::infoDirectory())->name('*.pid');
        if ($finder->hasResults()) {
            /** @var \SplFileInfo $file */
            foreach ($finder as $file) {
                $contents = json_decode($file->getContents(), JSON_OBJECT_AS_ARRAY);
                $listedPid = (int)$contents['pid'];
                if (ProcessUtility::isRunning($listedPid)) {
                    if ($requestedPid == $listedPid || $requestedPid === null) {
                        $result = posix_kill($listedPid, SIGTERM);
                        if ($result) {
                            $output->writeln('<info>stopped server with pid: '. $listedPid .'</info>');
                        } else {
                            $output->writeln('<error>failed stopping server with pid: '. $listedPid .'</error>');
                        }
                    }
                } else { // not running, clean up stale files
                    unlink(ProcessUtility::infoDirectory() . $contents['pid'] . '.pid');
                    $output->writeln('<info>removed stale server with pid: '. $listedPid .'</info>');
                }
            }
        } else {
            $output->writeln('<info>no process running</info>');
        }
        return Command::SUCCESS;
    }
}