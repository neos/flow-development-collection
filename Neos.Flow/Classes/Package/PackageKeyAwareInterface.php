<?php
namespace Neos\Flow\Package;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * An interface for packages that are aware of the concept of package keys, eg. "Neos.Flow".
 * For now that is every package registered.
 */
interface PackageKeyAwareInterface
{
    /**
     * Returns the package key of this package.
     *
     * @return string
     * @api
     */
    public function getPackageKey();
}
