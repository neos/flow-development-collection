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

use Neos\Utility\Arrays;
use Psr\Http\Message\UriInterface;

/**
 * Helper to extract information from Uris.
 */
final class UriHelper
{
    // this class only has static helpers
    private function __construct()
    {
    }

    /**
     * @var array<string, int>
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
     * Returns the path relative to the $baseUri
     *
     * @param UriInterface $baseUri The base URI to start from
     * @param UriInterface $uri The URI in question
     * @return string
     */
    public static function getRelativePath(UriInterface $baseUri, UriInterface $uri): string
    {
        $baseUriString = (string)$baseUri;
        $uriString = (string)$uri;
        if (empty($baseUriString) || !str_starts_with($uriString, $baseUriString)) {
            return '';
        }

        $baseUriPath = $baseUri->getPath();
        return substr_replace($uri->getPath(), '', 0, strlen($baseUriPath));
    }

    /**
     * Sets and replaces the query parameters.
     *
     * @param array<string, mixed> $queryParameters
     * @return UriInterface A new instance with the replaced query parameters.
     */
    public static function uriWithQueryParameters(UriInterface $uri, array $queryParameters): UriInterface
    {
        $query = http_build_query($queryParameters, '', '&');
        return $uri->withQuery($query);
    }

    /**
     * Merges the additional query parameters recursively into the current query parameters.
     *
     * @param array<string, mixed> $queryParameters
     * @return UriInterface A new instance with the additional query parameters.
     */
    public static function uriWithAdditionalQueryParameters(UriInterface $uri, array $queryParameters): UriInterface
    {
        if ($queryParameters === []) {
            return $uri;
        }
        if ($uri->getQuery() === '') {
            $mergedQueryParameters = $queryParameters;
        } else {
            $queryParametersFromUri = [];
            parse_str($uri->getQuery(), $queryParametersFromUri);
            $mergedQueryParameters = Arrays::arrayMergeRecursiveOverrule($queryParametersFromUri, $queryParameters);
        }
        return self::uriWithQueryParameters($uri, $mergedQueryParameters);
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
