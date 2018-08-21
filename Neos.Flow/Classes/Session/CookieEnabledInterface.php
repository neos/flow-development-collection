<?php
namespace Neos\Flow\Session;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Cookie;

/**
 * Interface for Sessions that are related to a cookie.
 */
interface CookieEnabledInterface extends SessionInterface
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
