<?php
namespace Neos\Flow\Http\Helper;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Psr\Http\Message\RequestInterface;

/**
 * Helper functions about request safety and security.
 */
abstract class SecurityHelper
{
    /**
     * Indicates if this request has been received through a secure channel.
     *
     * @param RequestInterface $request
     * @return bool
     */
    public static function isSecureRequest(RequestInterface $request): bool
    {
        // TODO: Isn't there a better way to figure this out?
        return $request->getUri()->getScheme() === 'https';
    }

    /**
     * Tells if the request method is "safe", that is, it is expected to not take any
     * other action than retrieval. This should the case with "GET" and "HEAD" requests.
     *
     * @param RequestInterface $request
     * @return bool
     */
    public static function hasSafeMethod(RequestInterface $request): bool
    {
        return (in_array($request->getMethod(), ['GET', 'HEAD']));
    }
}
