<?php
namespace TYPO3\Flow\Annotations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Marks a method as a signal for the signal/slot implementation
 * of Flow. The method will be augmented as needed (using AOP)
 * to be a usable signal.
 *
 * @Annotation
 * @Target("METHOD")
 */
final class Signal
{
}
