<?php
declare(strict_types=1);

namespace Neos\Flow\Configuration\Loader;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Core\ApplicationContext;

/**
 * The interface for a configuration loader
 */
interface LoaderInterface
{

    /**
     * Read configuration resources and return the final configuration array for the given configurationType
     *
     * @param array $packages An array of Package objects (indexed by package key) to consider
     * @param ApplicationContext $context
     * @return array The Configuration array for the current configurationType
     */
    public function load(array $packages, ApplicationContext $context) : array;
}
