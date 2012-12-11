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
 * Marks a class as an aspect.
 *
 * The class will be read by the AOP framework of Flow and inspected for
 * pointcut expressions and advice.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Aspect {}

?>