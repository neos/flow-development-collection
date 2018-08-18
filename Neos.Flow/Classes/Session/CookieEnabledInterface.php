<?php
namespace Neos\Flow\Session;

use Neos\Flow\Http\Cookie;

/**
 *
 */
interface CookieEnabledInterface
{
    /**
     * @return Cookie
     */
    public function getSessionCookie(): Cookie;

    /**
     * @param Cookie $sessionCookie
     * @param $storageIdentifier
     * @param $lastActivityTimestamp
     * @param array $tags
     * @return CookieEnabledInterface|SessionInterface
     */
    public static function createFromCookieAndSessionInformation(Cookie $sessionCookie, string $storageIdentifier, int $lastActivityTimestamp, array $tags = []);
}
