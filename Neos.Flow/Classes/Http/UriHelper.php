<?php

declare(strict_types=1);

namespace Neos\Flow\Http;

use Neos\Utility\Arrays;
use Psr\Http\Message\UriInterface;

final class UriHelper
{
    // this class only has static helpers
    private function __construct()
    {
    }

    /**
     * Merges recursively into the current {@see UriInterface::getQuery} these additional query parameters.
     *
     * @param array $queryParameters
     * @return UriInterface A new instance with the additional query.
     */
    public static function withAdditionalQueryParameters(UriInterface $uri, array $queryParameters): UriInterface
    {
        if ($queryParameters === []) {
            return $uri;
        }
        if ($uri->getQuery() === '') {
            $mergedQuery = $queryParameters;
        } else {
            $queryParametersFromUri = [];
            parse_str($uri->getQuery(), $queryParametersFromUri);
            $mergedQuery = Arrays::arrayMergeRecursiveOverrule($queryParametersFromUri, $queryParameters);
        }
        return $uri->withQuery(http_build_query($mergedQuery, '', '&'));
    }
}
