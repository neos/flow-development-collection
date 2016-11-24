<?php
namespace Neos\Flow\Tests\Functional\Property\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\InterfaceA;

/**
 * A simple class for PropertyMapper test
 *
 */
class TestClassWithSingletonConstructorInjection
{

    /**
     * @var InterfaceA
     */
    protected $singletonClass;

    /**
     * @param InterfaceA $singletonClass
     */
    public function __construct(InterfaceA $singletonClass)
    {
        $this->singletonClass = $singletonClass;
    }

    /**
     * @return InterfaceA
     */
    public function getSingletonClass()
    {
        return $this->singletonClass;
    }
}
