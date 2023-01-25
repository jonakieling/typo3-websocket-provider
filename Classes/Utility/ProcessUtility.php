<?php

namespace Werkraum\WebsocketProvider\Utility;

use TYPO3\CMS\Core\Core\Environment;

class ProcessUtility
{
    /**
     * Creates the directory and returns the path to store the WebSocket server process files.
     *
     * @return string
     */
    public static function infoDirectory()
    {
        $directory = Environment::getVarPath() . '/websocket_provider/';
        @mkdir($directory);
        return $directory;
    }

    /**
     * Checks via shell command whether a process is still running
     *
     * @param $pid
     * @return bool
     */
    public static function isRunning($pid)
    {
        exec(sprintf('ps -p %s -o pid=', $pid), $out);
        return trim($out[0]) == $pid;
    }

    /**
     * Creates a info file for a server process
     *
     * @param $pid
     * @param $address
     * @param $componentName
     * @return void
     */
    public static function saveInfoFile($pid, $address)
    {
        touch(ProcessUtility::infoDirectory() . $pid . '.pid');
        file_put_contents(
            ProcessUtility::infoDirectory() . $pid . '.pid',
            json_encode([
                'pid' => $pid,
                'address' => $address,
            ])
        );
    }
}