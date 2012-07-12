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
 * Marks a property or class as lazy-loaded.
 *
 * This is only relevant for anything based on the generic persistence
 * layer of FLOW3. For Doctrine based persistence this is ignored.
 *
 * @Annotation
 * @Target({"CLASS", "PROPERTY"})
 */
final class Lazy {}

?>