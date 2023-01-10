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
use Symfony\Component\Finder\Finder;
use Werkraum\WebsocketProvider\Utility\ProcessUtility;

class ListServerCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $finder = new Finder();
        $finder->files()->in(ProcessUtility::infoDirectory())->name('*.pid');
        if ($finder->hasResults()) {
            /** @var \SplFileInfo $file */
            foreach ($finder as $file) {
                $contents = json_decode($file->getContents(), JSON_OBJECT_AS_ARRAY);
                if (ProcessUtility::isRunning($contents['pid'])) {
                    $output->writeln(
                        sprintf(
                            '<info>%s running on %s with pid %d</info>',
                            $contents['component'],
                            $contents['address'],
                            $contents['pid'],
                        )
                    );
                } else {
                    $output->writeln(
                        sprintf(
                            '<warning>%s running on %s with pid %d (stale)</warning>',
                            $contents['component'],
                            $contents['address'],
                            $contents['pid'],
                        )
                    );
                }
            }
        } else {
            $output->writeln('<info>no server running</info>');
        }
        return Command::SUCCESS;
    }
}