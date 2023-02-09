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
use Werkraum\WebsocketProvider\Interfaces\CustomRouteInterface;

class ListRoutesCommand extends Command
{

    /**
     * @var iterable
     */
    protected $components;

    /**
     * @param iterable $components
     */
    public function __construct(iterable $components)
    {
        parent::__construct();
        $this->components = $components;
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->components as $component) {
            if ($component instanceof CustomRouteInterface) {
                $path = $component->getPath();
            } else {
                $path = '/' . str_replace("\\", '_', get_class($component));
            }
            $output->writeln("<info>{$path}</info> " . get_class($component));
        }
        return Command::SUCCESS;
    }
}