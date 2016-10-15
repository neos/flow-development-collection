<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

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
 * Class CommonObject
 * Representation of an object handled as "\Doctrine\DBAL\Types\Type::OBJECT"
 */
class CommonObject
{
    /**
     * @var string
     */
    protected $foo;

    /**
     * @param string $foo
     * @return $this
     */
    public function setFoo($foo = null)
    {
        $this->foo = $foo;
        return $this;
    }

    /**
     * @return string
     */
    public function getFoo()
    {
        return $this->foo;
    }
}
