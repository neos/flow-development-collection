<?php
namespace TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model;

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
 * A model fixture which is used for testing the class schema building
 *
 * @Flow\Entity
 */
class SubSubSubEntity extends SubSubEntity
{
    /**
     * Just yet another other normal string
     *
     * @var string
     */
    protected $yetAnotherOtherString;
}
