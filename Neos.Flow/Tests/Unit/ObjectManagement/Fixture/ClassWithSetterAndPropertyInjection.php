<?php
namespace Neos\Flow\Tests\Unit\ObjectManagement\Fixture;

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
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

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
