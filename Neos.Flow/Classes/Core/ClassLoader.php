<?php
namespace Neos\Flow\Core;

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
use Neos\Flow\Package;
use Neos\Utility\Files;

/**
 * Class Loader implementation as fallback to the compoer loader and for test classes.
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class ClassLoader
{
    /**
     * @var string
     */
    const MAPPING_TYPE_PSR0 = 'psr-0';

    /**
     * @var string
     */
    const MAPPING_TYPE_PSR4 = 'psr-4';

    /**
     * @param array $defaultPackageEntries Adds default entries for packages that should be available for very early loading
     */
    public function __construct(array $defaultPackageEntries = [])
    {
    }

    /**
     * Is the given mapping type predictable in terms of path to class name
     *
     * @param string $mappingType
     * @return boolean
     */
    public static function isAutoloadTypeWithPredictableClassPath(string $mappingType): bool
    {
        return ($mappingType === static::MAPPING_TYPE_PSR0 || $mappingType === static::MAPPING_TYPE_PSR4);
    }
}
