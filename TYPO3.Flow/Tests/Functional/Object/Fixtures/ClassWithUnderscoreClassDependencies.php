<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * A class which has dependencies to classes with underscores
 */
class ClassWithUnderscoreClassDependencies
{
    /**
     * @Flow\Inject(lazy=false)
     * @var Class_With_Underscores
     */
    public $classWithUnderscores;
}
