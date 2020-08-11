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

use Neos\Utility\MediaTypes;
use Psr\Http\Message\RequestInterface;

/**
 * Helper for dealing with HTTP media type resolution.
 */
abstract class MediaTypeHelper
{
    /**
     * Get accepted media types for the given request.
     * If no "Accept" header was found all media types are acceptable.
     *
     * @param RequestInterface $request
     * @return array
     */
    public static function determineAcceptedMediaTypes(RequestInterface $request): array
    {
        $rawValues = $request->getHeaderLine('Accept');
        if (empty($rawValues) || !is_string($rawValues)) {
            return ['*/*'];
        }
        $acceptedMediaTypes = self::parseContentNegotiationQualityValues($rawValues);

        return $acceptedMediaTypes;
    }

    /**
     * Returns the best fitting IANA media type after applying the content negotiation
     * rules on the accepted media types.
     *
     * @param array $acceptedMediaTypes A list of accepted media types according to a request.
     * @param array $supportedMediaTypes A list of media types which are supported by the application / controller
     * @param bool $trim If TRUE, only the type/subtype of the media type is returned. If FALSE, the full original media type string is returned.
     * @return string The media type and sub type which matched, NULL if none matched
     */
    public static function negotiateMediaType(array $acceptedMediaTypes, array $supportedMediaTypes, bool $trim = true): ?string
    {
        $negotiatedMediaType = null;
        foreach ($acceptedMediaTypes as $acceptedMediaType) {
            foreach ($supportedMediaTypes as $supportedMediaType) {
                if (MediaTypes::mediaRangeMatches($acceptedMediaType, $supportedMediaType)) {
                    $negotiatedMediaType = $supportedMediaType;
                    break 2;
                }
            }
        }

        return ($trim && $negotiatedMediaType !== null ? MediaTypes::trimMediaType($negotiatedMediaType) : $negotiatedMediaType);
    }

    /**
     * Parses a RFC 2616 content negotiation header field by evaluating the Quality
     * Values and splitting the options into an array list, ordered by user preference.
     *
     * @param string $rawValues The raw Accept* Header field value
     * @return array The parsed list of field values, ordered by user preference
     */
    public static function parseContentNegotiationQualityValues(string $rawValues): array
    {
        $acceptedTypes = array_map(
            function ($acceptType) {
                $typeAndQuality = preg_split('/;\s*q=/', $acceptType);

                return [$typeAndQuality[0], (isset($typeAndQuality[1]) ? (float)$typeAndQuality[1] : '')];
            },
            preg_split('/,\s*/', $rawValues)
        );

        $flattenedAcceptedTypes = [];
        $valuesWithoutQualityValue = [[], [], [], []];
        foreach ($acceptedTypes as $typeAndQuality) {
            if ($typeAndQuality[1] === '') {
                $parsedType = MediaTypes::parseMediaType($typeAndQuality[0]);
                if ($parsedType['type'] === '*') {
                    $valuesWithoutQualityValue[3][$typeAndQuality[0]] = true;
                } elseif ($parsedType['subtype'] === '*') {
                    $valuesWithoutQualityValue[2][$typeAndQuality[0]] = true;
                } elseif ($parsedType['parameters'] === []) {
                    $valuesWithoutQualityValue[1][$typeAndQuality[0]] = true;
                } else {
                    $valuesWithoutQualityValue[0][$typeAndQuality[0]] = true;
                }
            } else {
                $flattenedAcceptedTypes[$typeAndQuality[0]] = $typeAndQuality[1];
            }
        }
        $valuesWithoutQualityValue = array_merge(array_keys($valuesWithoutQualityValue[0]), array_keys($valuesWithoutQualityValue[1]), array_keys($valuesWithoutQualityValue[2]), array_keys($valuesWithoutQualityValue[3]));
        arsort($flattenedAcceptedTypes);
        $parsedValues = array_merge($valuesWithoutQualityValue, array_keys($flattenedAcceptedTypes));

        return $parsedValues;
    }
}
