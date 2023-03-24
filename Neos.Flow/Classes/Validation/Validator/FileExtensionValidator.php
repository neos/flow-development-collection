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
 * The given $value is has one of the allowed file extensions
 * Note: a value of NULL or empty string ('') are considered valid
 */
class FileExtensionValidator extends AbstractValidator
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
        'allowedExtensions' => [[], 'Array of allowed file extensions', 'array', true]
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
            $filename = $value->getClientFilename();
        } elseif ($value instanceof ResourceMetaDataInterface) {
            $filename = $value->getFilename();
        } else {
            $this->addError('Only Uploads or Resources are supported.', 1677934927);
            return;
        }

        $fileExtension =  pathinfo((string)$filename, PATHINFO_EXTENSION);

        if ($fileExtension === null || $fileExtension === '') {
            $this->addError('The file has no file extension.', 1677934932);
            return;
        }
        if (!in_array($fileExtension, $this->options['allowedExtensions'])) {
            $this->addError('The file extension "%s" is not allowed.', 1677934939, [$fileExtension]);
            return;
        }
    }
}
