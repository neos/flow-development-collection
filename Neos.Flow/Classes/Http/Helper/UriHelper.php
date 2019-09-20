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
     * @var array
     */
    private static $defaultPortsByScheme = [
        'http' => 80,
        'https' => 443,
        'ftp' => 21,
        'gopher' => 70,
        'nntp' => 119,
        'news' => 119,
        'telnet' => 23,
        'tn3270' => 23,
        'imap' => 143,
        'pop' => 110,
        'ldap' => 389,
    ];

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
        $baseUriString = (string)$baseUri;
        $uriString = (string)$uri;
        if (empty($baseUriString) || strpos($uriString, $baseUriString) !== 0) {
            return '';
        }

        $baseUriPath = $baseUri->getPath();
        return substr_replace($uri->getPath(), '', 0, strlen($baseUriPath));
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

    /**
     * @param string $scheme
     * @return int|null
     */
    public static function getDefaultPortForScheme(string $scheme): ?int
    {
        return self::$defaultPortsByScheme[strtolower($scheme)] ?? null;
    }
}
