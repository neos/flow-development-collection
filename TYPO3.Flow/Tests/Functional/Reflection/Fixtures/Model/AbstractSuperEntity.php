<?php
namespace TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A model fixture
 *
 * It is not used directly in any test but makes sure that #31522 is fixed.
 * Without the fix (I51d33c69be577ce0d1c0f663042c0de5ec6109e7) the compile step
 * of the functional tests fails because of this class.
 *
 * @Flow\Entity
 */
abstract class AbstractSuperEntity
{
}
