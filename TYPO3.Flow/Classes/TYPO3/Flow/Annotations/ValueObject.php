<?php
namespace TYPO3\Flow\Annotations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Marks the annotate class as a value object.
 *
 * Regarding Doctrine the object is treated like an entity, but Flow
 * applies some optimizations internally, e.g. to store only one instance
 * of a value object.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class ValueObject
{
}
