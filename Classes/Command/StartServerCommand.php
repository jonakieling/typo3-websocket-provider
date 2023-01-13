<?php

/**
 * This file is part of the "websocket_provider" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Werkraum\WebsocketProvider\Command;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Werkraum\WebsocketProvider\Factory\ServerFactory;
use Werkraum\WebsocketProvider\Utility\ProcessUtility;

class StartServerCommand extends Command
{
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
        parent::__construct();
        $this->config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('websocket_provider');
        $this->loop = Loop::get();
    }

    protected function configure()
    {
        $this->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Host IP to listen on', '0.0.0.0');
        $this->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Port to listen on', '18080');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $server = (new ServerFactory)
            ->setLoop($this->loop)
            ->setHost($input->getOption('host'))
            ->setPort($input->getOption('port'))
            ->create();

        $output->writeln(
            sprintf(
                '<info>%s running on %s with pid %d</info>',
                $this->config['app']['component'],
                $server->socket->getAddress(),
                getmypid()
            )
        );

        ProcessUtility::saveInfoFile(getmypid(), $server->socket->getAddress(), $this->config['app']['component']);

        $server->run();

        return Command::SUCCESS;
    }
}