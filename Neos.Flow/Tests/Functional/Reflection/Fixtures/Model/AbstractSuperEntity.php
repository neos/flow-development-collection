<?php
namespace Neos\Flow\Tests\Functional\Reflection\Fixtures\Model;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

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
