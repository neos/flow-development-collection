<?php
namespace TYPO3\FLOW3\Annotations;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Marks the annotate class as a value object.
 *
 * Regarding Doctrine the object is treated like an entity, but FLOW3
 * applies some optimizations internally, e.g. to store only one instance
 * of a value object.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class ValueObject {}

?>