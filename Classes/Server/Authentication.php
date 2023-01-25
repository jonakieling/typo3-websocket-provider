<?php

namespace Werkraum\WebsocketProvider\Server;

use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;
use Ratchet\MessageComponentInterface;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
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
    protected static $cookieParts = array(
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
    protected function parseCookie($cookie, $host = null, $path = null, $decode = false) {
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
     * Attaches TYPO3 UserAspects for frontend and backend users to the connection.
     *
     * @param RequestInterface|null $request
     * @param ConnectionInterface $conn
     * @return void
     */
    protected function addUserAspects(?RequestInterface $request, ConnectionInterface $conn): void
    {
        // todo handle logoff, cookie remove, invalid session

        $cookies = [];
        foreach ($request->getHeader('Cookie') as $item) {
            $cookies = array_merge($cookies, $this->parseCookie($item)['cookies']);
        }

        $beCookieName = trim($GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName']);
        if (isset($cookies[$beCookieName])) {
            $conn->beUser = $this->fetchUserAspect(
                BackendUserAuthentication::class,
                $beCookieName,
                $cookies[$beCookieName]
            );
        } else {
            $conn->beUser = GeneralUtility::makeInstance(UserAspect::class);
        }

        $feCookieName = trim($GLOBALS['TYPO3_CONF_VARS']['FE']['cookieName']);
        if (isset($cookies[$feCookieName])) {
            $conn->feUser = $this->fetchUserAspect(
                FrontendUserAuthentication::class,
                $feCookieName,
                $cookies[$feCookieName]
            );
        } else {
            $conn->feUser = GeneralUtility::makeInstance(UserAspect::class);
        }
    }

    /**
     * Loads a existing user session and creates a UserAspect for it.
     *
     * @param string $authenticationClass
     * @param string $cookieName
     * @param string $sessionId
     * @return void
     */
    protected function fetchUserAspect(string $authenticationClass, string $cookieName, string $sessionId): UserAspect
    {
        /**
         * The session cookie is set temporarily since the TYPO3 authentication fetches the id only from the global.
         */
        $_COOKIE[$cookieName] = $sessionId;
        /** @var AbstractUserAuthentication $user */
        $user = GeneralUtility::makeInstance($authenticationClass);
        $user->start();
        $user->fetchGroupData(); // group data needs to be loaded once to be accessible later
        unset($_COOKIE[$cookieName]);

        return GeneralUtility::makeInstance(UserAspect::class, $user);
    }
}