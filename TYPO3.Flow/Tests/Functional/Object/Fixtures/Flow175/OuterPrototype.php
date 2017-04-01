<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures\Flow175;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class OuterPrototype
{
    /**
     * @var GreeterInterface
     */
    private $inner;

    public function __construct(GreeterInterface $inner)
    {
        $this->inner = $inner;
    }

    /**
     * @return Greeter
     */
    public function getInner()
    {
        return $this->inner;
    }
}
