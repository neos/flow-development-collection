<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A target class for testing introductions. Set as being an entity to check if
 * property introductions are correctly picked up by the persistence.
 *
 * @Flow\Entity
 */
class TargetClass04
{
}
