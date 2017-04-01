<?php
namespace TYPO3\Flow\Tests\Functional\Property\Fixtures;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A simple interface for PropertyMapper test
 */
interface TestEntityInterface
{
    /**
     * @return string
     */
    public function getName();
}
