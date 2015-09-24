<?php
namespace TYPO3\Flow\Annotations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Marks a property as being (part of) the identity of an object.
 *
 * If multiple properties are annotated as Identity, a compound
 * identity is created.
 *
 * For Doctrine a unique key over all involved properties will be
 * created - thus the limitations of that need to be observed.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Identity
{
}
