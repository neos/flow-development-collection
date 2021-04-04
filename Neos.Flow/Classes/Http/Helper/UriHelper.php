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

use Psr\Http\Message\UriInterface;

/**
 * Helper to extract information from Uris.
 */
abstract class UriHelper
{
    /**
     * Get the username component of the given Uri
     *
     * @param UriInterface $uri
     * @return string If the URI had no username an empty string is returned.
     */
    public static function getUsername(UriInterface $uri): string
    {
        $userInfo = explode(':', $uri->getUserInfo());
        return (isset($userInfo[0]) ? $userInfo[0] : '');
    }

    /**
     * Get the password component of the given Uri
     *
     * @param UriInterface $uri
     * @return string If the URI had no password an empty string is returned.
     */
    public static function getPassword(UriInterface $uri): string
    {
        $userInfo = explode(':', $uri->getUserInfo());

        return (isset($userInfo[1]) ? $userInfo[1] : '');
    }

    /**
     * Returns the path relative to the $baseUri
     *
     * @param UriInterface $baseUri The base URI to start from
     * @param UriInterface $uri The URI in quesiton
     * @return string
     */
    public static function getRelativePath(UriInterface $baseUri, UriInterface $uri): string
    {
        // FIXME: We should probably do a strpos === 0 instead to make sure the baseUri actually matches the start of the Uri.
        $baseUriLength = strlen($baseUri->getPath());
        if ($baseUriLength >= strlen($uri->getPath())) {
            return '';
        }

        return substr($uri->getPath(), $baseUriLength);
    }

    /**
     * Parses the URIs query string into an array of arguments
     *
     * @param UriInterface $uri
     * @return array
     */
    public static function parseQueryIntoArguments(UriInterface $uri): array
    {
        $arguments = [];
        parse_str($uri->getQuery(), $arguments);
        return $arguments;
    }

    /**
     * Returns an Uri object with the query string being generated from the array of arguments given
     *
     * @param UriInterface $uri
     * @param array $arguments
     * @return UriInterface
     */
    public static function uriWithArguments(UriInterface $uri, array $arguments): UriInterface
    {
        $query = http_build_query($arguments, '', '&', PHP_QUERY_RFC3986);
        return $uri->withQuery($query);
    }
}
