<?php
namespace TYPO3\Flow\Persistence\Generic\Aspect;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
