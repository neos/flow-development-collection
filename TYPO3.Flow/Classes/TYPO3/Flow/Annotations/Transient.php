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
 * Marks a property as transient - it will never be considered by the
 * persistence layer for storage and retrieval.
 *
 * Useful for calculated values and any other properties only needed
 * during runtime.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Transient {}

?>