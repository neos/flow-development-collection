<?php
declare(strict_types=1);

namespace Neos\Flow\Validation\Validator;

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
use Neos\Flow\ResourceManagement\ResourceMetaDataInterface;
use Neos\Utility\MediaTypes;
use Psr\Http\Message\UploadedFileInterface;

/**
 * The given $value is matches the defined medis types
 * Note: a value of NULL or empty string ('') are considered valid
 */
class MediaTypeValidator extends AbstractValidator
{
    /**
     * This contains the supported options, each being an array of:
     *
     * 0 => default value
     * 1 => description
     * 2 => type
     * 3 => required (boolean, optional)
     *
     * @var array
     */
    protected $supportedOptions = [
        'allowedTypes' => [[], 'Array of allowed media ranges', 'array', true],
        'disallowedTypes' => [[], 'Array of disallowed media ranges', 'array', false],
    ];

    /**
     * The given $value is valid media type matches one of the allowedTypes and
     * none of the disallowedTypes
     *
     * Note: a value of NULL or empty string ('') is considered valid and was handled already
     *
     * @param mixed $value
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        if ($value instanceof UploadedFileInterface) {
            $mediaType = $value->getClientMediaType();
        } elseif ($value instanceof ResourceMetaDataInterface) {
            $mediaType = $value->getMediaType();
        } else {
            $this->addError('Only Uploads or Resources are supported.', 1677928909);
            return;
        }

        if ($mediaType === null) {
            $this->addError('No media type was found.', 1677938309);
            return;
        }

        if ($this->options['allowedTypes']) {
            $matched = false;
            foreach ($this->options['allowedTypes'] as $allowedMediaRange) {
                if (MediaTypes::mediaRangeMatches($allowedMediaRange, $mediaType)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $this->addError('Media type %s is not allowed.', 1677929196, [$mediaType]);
                return;
            }
        }

        if ($this->options['disallowedTypes']) {
            foreach ($this->options['disallowedTypes'] as $disAllowedMediaRange) {
                if (MediaTypes::mediaRangeMatches($disAllowedMediaRange, $mediaType)) {
                    $this->addError('Media type %s is forbidden.', 1677929309, [$mediaType, $disAllowedMediaRange]);
                    return;
                }
            }
        }
    }
}
