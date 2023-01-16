<?php

namespace Werkraum\WebsocketProvider\Server;

use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;
use Ratchet\MessageComponentInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

class Authentication implements HttpServerInterface
{
    /**
     * @var MessageComponentInterface
     */
    protected $app;

    /**
     * @var ConnectionPool
     */
    protected ConnectionPool $connectionPool;

    /**
     * @param ConnectionPool $connectionPool
     */
    public function __construct(MessageComponentInterface $app)
    {
        $this->app = $app;
    }

    /**
     * @inheritDoc
     */
    function onClose(ConnectionInterface $conn)
    {
        $this->app->onClose($conn);
    }

    /**
     * @inheritDoc
     */
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->app->onError($conn, $e);
    }

    /**
     * @inheritDoc
     */
    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        $this->addUserAspects($request, $conn);

        if ($this->app instanceof HttpServerInterface) {
            $this->app->onOpen($conn, $request);
        } else {
            $this->app->onOpen($conn);
        }
    }

    /**
     * @inheritDoc
     */
    function onMessage(ConnectionInterface $from, $msg)
    {
        $this->app->onMessage($from, $msg);
    }

    /**
     * Taken from Guzzle3
     */
    private static $cookieParts = array(
        'domain'      => 'Domain',
        'path'        => 'Path',
        'max_age'     => 'Max-Age',
        'expires'     => 'Expires',
        'version'     => 'Version',
        'secure'      => 'Secure',
        'port'        => 'Port',
        'discard'     => 'Discard',
        'comment'     => 'Comment',
        'comment_url' => 'Comment-Url',
        'http_only'   => 'HttpOnly'
    );

    /**
     * Taken from Guzzle3
     */
    private function parseCookie($cookie, $host = null, $path = null, $decode = false) {
        // Explode the cookie string using a series of semicolons
        $pieces = array_filter(array_map('trim', explode(';', $cookie)));

        // The name of the cookie (first kvp) must include an equal sign.
        if (empty($pieces) || !strpos($pieces[0], '=')) {
            return false;
        }

        // Create the default return array
        $data = array_merge(array_fill_keys(array_keys(self::$cookieParts), null), array(
            'cookies'   => array(),
            'data'      => array(),
            'path'      => $path ?: '/',
            'http_only' => false,
            'discard'   => false,
            'domain'    => $host
        ));
        $foundNonCookies = 0;

        // Add the cookie pieces into the parsed data array
        foreach ($pieces as $part) {

            $cookieParts = explode('=', $part, 2);
            $key = trim($cookieParts[0]);

            if (count($cookieParts) == 1) {
                // Can be a single value (e.g. secure, httpOnly)
                $value = true;
            } else {
                // Be sure to strip wrapping quotes
                $value = trim($cookieParts[1], " \n\r\t\0\x0B\"");
                if ($decode) {
                    $value = urldecode($value);
                }
            }

            // Only check for non-cookies when cookies have been found
            if (!empty($data['cookies'])) {
                foreach (self::$cookieParts as $mapValue => $search) {
                    if (!strcasecmp($search, $key)) {
                        $data[$mapValue] = $mapValue == 'port' ? array_map('trim', explode(',', $value)) : $value;
                        $foundNonCookies++;
                        continue 2;
                    }
                }
            }

            // If cookies have not yet been retrieved, or this value was not found in the pieces array, treat it as a
            // cookie. IF non-cookies have been parsed, then this isn't a cookie, it's cookie data. Cookies then data.
            $data[$foundNonCookies ? 'data' : 'cookies'][$key] = $value;
        }

        // Calculate the expires date
        if (!$data['expires'] && $data['max_age']) {
            $data['expires'] = time() + (int) $data['max_age'];
        }

        return $data;
    }

    /**
     * Attaches a TYPO3 Context to the connection.
     *
     * @param RequestInterface|null $request
     * @param ConnectionInterface $conn
     * @return void
     */
    protected function addUserAspects(?RequestInterface $request, ConnectionInterface $conn): void
    {
        $cookies = $this->parseCookie($request->getHeader('Cookie')[0])['cookies'];
        if (isset($cookies['be_typo_user'])) {
            $_COOKIE['be_typo_user'] = $cookies['be_typo_user'];
            $beUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
            $beUser->start();
            $beUser->fetchGroupData();
            $conn->beUser = GeneralUtility::makeInstance(UserAspect::class, $beUser);
            unset($_COOKIE['be_typo_user']);
        }
        if (isset($cookies['fe_typo_user'])) {
            $_COOKIE['fe_typo_user'] = $cookies['fe_typo_user'];
            $feUser = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
            $feUser->start();
            $feUser->fetchGroupData();
            $conn->feUser = GeneralUtility::makeInstance(UserAspect::class, $feUser);
            unset($_COOKIE['fe_typo_user']);
        }
    }
}