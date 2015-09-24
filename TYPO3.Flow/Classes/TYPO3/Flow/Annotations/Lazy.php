<?php
namespace TYPO3\Flow\Annotations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Marks a property or class as lazy-loaded.
 *
 * This is only relevant for anything based on the generic persistence
 * layer of Flow. For Doctrine based persistence this is ignored.
 *
 * @Annotation
 * @Target({"CLASS", "PROPERTY"})
 */
final class Lazy
{
}
