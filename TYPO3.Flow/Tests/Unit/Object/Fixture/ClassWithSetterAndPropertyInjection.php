<?php
namespace TYPO3\Flow\Tests\Object\Fixture;

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
use TYPO3\Flow\Object\ObjectManagerInterface;

/**
 */
class ClassWithSetterAndPropertyInjection
{
    /**
     * @var \TYPO3\Foo\Bar
     * @Flow\Inject
     */
    protected $firstDependency;

    /**
     * @var \TYPO3\Coffee\Bar
     * @Flow\Inject
     */
    protected $secondDependency;

    /**
     * @param ObjectManagerInterface $firstDependency
     */
    public function injectFirstDependency(ObjectManagerInterface $firstDependency)
    {
        $this->firstDependency = $firstDependency;
    }
}
