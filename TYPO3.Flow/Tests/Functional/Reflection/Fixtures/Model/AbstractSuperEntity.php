<?php
namespace TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures\Model;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A model fixture
 *
 * It is not used directly in any test but makes sure that #31522 is fixed.
 * Without the fix (I51d33c69be577ce0d1c0f663042c0de5ec6109e7) the compile step
 * of the functional tests fails because of this class.
 *
 * @FLOW3\Entity
 */
abstract class AbstractSuperEntity {}

?>