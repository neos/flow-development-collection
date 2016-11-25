<?php
namespace Neos\Flow\Persistence\Generic\Aspect;

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
 * An interface used to introduce certain methods to support lazy loading objects
 *
 */
interface LazyLoadingObjectInterface
{
    /**
     * Signifies lazy loading of properties in an object
     * @type integer
     */
    const LAZY_PROPERTIES = 1;

    /**
     * Signifies lazy loading of properties in a SplObjectStorage
     * @type integer
     */
    const LAZY_OBJECTSTORAGE = 2;

    /**
     * Introduces an initialization method.
     *
     * @return void
     */
    public function Flow_Persistence_LazyLoadingObject_initialize();
}
