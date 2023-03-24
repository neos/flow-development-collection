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
use Psr\Http\Message\UploadedFileInterface;

/**
 * Validator for file sizes
 * Note: a value of NULL or empty string ('') are considered valid
 */
class FileSizeValidator extends AbstractValidator
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
        'minimum' => [null, 'Minimum allowed filesize in bytes', 'integer', false],
        'maximum' => [null, 'Maximum allowed filesize in bytes', 'integer', false]
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
            $filesize = $value->getSize();
        } elseif ($value instanceof ResourceMetaDataInterface) {
            $filesize = $value->getFileSize();
        } else {
            $this->addError('Only Uploads or Resources are supported.', 1677934918);
            return;
        }

        if ($filesize === null) {
            $this->addError('The file has no size.', 1677934912);
            return;
        }
        if ($this->options['minimum'] && $filesize < $this->options['minimum']) {
            $this->addError('The file is larger than allowed.', 1677934908);
            return;
        }
        if ($this->options['maximum'] && $filesize > $this->options['maximum']) {
            $this->addError('The file is smaller than allowed.', 1677934903);
            return;
        }
    }
}
