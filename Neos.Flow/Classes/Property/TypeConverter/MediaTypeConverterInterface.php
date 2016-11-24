<?php
namespace Neos\Flow\Property\TypeConverter;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\TypeConverterInterface;

/**
 * A marker interface for type converters that are used to decode the content of a HTTP request
 *
 * To extend the default media type conversion of HTTP requests, this interface can be implemented by a custom TypeConverter
 * and then set as default implementation via Objects.yaml:
 *
 * 'Neos\Flow\Property\TypeConverter\MediaTypeConverterInterface'.className: Some\Custom\TypeConverter
 *
 * @api
 */
interface MediaTypeConverterInterface extends TypeConverterInterface
{
    /**
     * Name of the configuration option that contains the expected media type. This is usually set by the ActionRequest and
     * corresponds to the browser's Content-Type header
     *
     * @var string
     */
    const CONFIGURATION_MEDIA_TYPE = 'mediaType';

    /**
     * The default media type that should be used if no explicit media type was configured (see CONFIGURATION_MEDIA_TYPE)
     *
     * @var string
     */
    const DEFAULT_MEDIA_TYPE = 'application/json';
}
