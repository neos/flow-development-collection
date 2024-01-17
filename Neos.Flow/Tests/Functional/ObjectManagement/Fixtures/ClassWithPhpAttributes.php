<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

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
 * A class with PHP 8 attributes
 * @Flow\Scope("prototype")
 */
#[SampleAttribute(ClassWithPhpAttributes::class, options: ['baz', 'quux'])]
#[SampleAttribute(ClassWithPhpAttributes::class, ['foo' => 'bar'])]
class ClassWithPhpAttributes
{
    /**
     * @return void
     */
    #[Flow\Around(pointcutExpression: "method(somethingImpossible())")]
    #[Flow\Session(autoStart: false)]
    public function methodWithAttributes(): void
    {
    }
}
