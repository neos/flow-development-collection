<?php
namespace Neos\Flow\Tests\Functional\Aop\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A target class for testing the AOP framework
 */
class ChildClassOfTargetClass01 extends TargetClass01
{
    /**
     * @return string
     */
    public function sayHello()
    {
        return 'Greetings, I just wanted to say: ' . parent::sayHello();
    }

    /**
     * @return string
     */
    public function saySomethingSmart()
    {
        return parent::saySomethingSmart() . ' That was smart, eh?';
    }

    /**
     * @return string
     */
    public function sayWhatFlowIs()
    {
        return 'Flow is not';
    }
}
