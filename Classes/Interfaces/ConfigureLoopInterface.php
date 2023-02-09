<?php

namespace Werkraum\WebsocketProvider\Interfaces;

use React\EventLoop\LoopInterface;

interface ConfigureLoopInterface
{
    /**
     * @param LoopInterface $loop
     * @return void
     */
    public function configureLoop(LoopInterface $loop): void;
}