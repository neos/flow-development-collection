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

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Neos\Flow\Annotations as Flow;

/**
 * A class which has doctrine ObjectManager / EntityManagerInterface injections
 */
class ClassWithDoctrineInjections
{
    /**
     * @Flow\Inject(lazy = FALSE)
     * @var ObjectManager
     */
    public $objectManager;

    /**
     * @Flow\Inject(lazy = FALSE)
     * @var EntityManagerInterface
     */
    public $entityManager;
}
