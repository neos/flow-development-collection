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

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Tests\Functional\Persistence\Fixtures as PF;

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
     * @param SubEntity $parameter
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
     * @param SubEntity|null $parameter
     * @return void
     */
    public function nullableClassName(SubEntity $parameter)
    {
    }

    /**
     * @param float $parameter
     * @return void
     */
    public function simpleType($parameter)
    {
    }

    /**
     * @param array<SubSubEntity> $param2 some description
     */
    public function multipleParamsWithPartialAnnotationCoverage(SubEntity $param1, array $param2, SubSubSubEntity|null $param3 = null): void
    {
    }
}
