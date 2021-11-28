<?php
namespace Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller;

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
 * An interface to test dynamic type resolution and validation
 */
interface TestObjectInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getEmailAddress();
}
