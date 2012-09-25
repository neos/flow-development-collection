<?php
namespace TYPO3\Flow\Annotations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
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
final class Lazy {}

?>