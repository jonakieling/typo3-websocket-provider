<?php

namespace Werkraum\WebsocketProvider\Loop;

use React\EventLoop\LoopInterface;

interface ConfigureLoopInterface
{
    /**
     * @param LoopInterface $loop
     * @return void
     */
    public function configureLoop(LoopInterface $loop): void;
}