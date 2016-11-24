<?php
namespace Neos\Flow\Tests\Functional\Reflection\Fixtures\Model;

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
use Neos\Flow\Tests\Functional\Persistence\Fixtures as PF;
use Neos\Flow\Tests\Functional\Reflection\Fixtures;
use Doctrine\ORM\Mapping as ORM;

/**
 * A model fixture which is used for testing the class schema building
 *
 * @Flow\Entity
 */
class EntityWithUseStatements
{
    /**
     * @var SubSubEntity
     * @ORM\OneToOne
     */
    protected $subSubEntity;

    /**
     * @var PF\SubEntity
     * @ORM\OneToOne
     */
    protected $propertyFromOtherNamespace;

    /**
     * @param Fixtures\Model\SubEntity $parameter
     * @return void
     */
    public function fullyQualifiedClassName(SubEntity $parameter)
    {
    }

    /**
     * @param PF\SubEntity $parameter
     * @return void
     */
    public function aliasedClassName(SubEntity $parameter)
    {
    }

    /**
     * @param SubEntity $parameter
     * @return void
     */
    public function relativeClassName(SubEntity $parameter)
    {
    }

    /**
     * @param float $parameter
     * @return void
     */
    public function simpleType($parameter)
    {
    }
}
