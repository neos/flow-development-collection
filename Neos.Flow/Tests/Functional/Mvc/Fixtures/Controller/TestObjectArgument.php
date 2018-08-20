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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Neos\Flow\Annotations as Flow;

/**
 * An object argument with validation
 */
class TestObjectArgument
{
    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     */
    protected $name;

    /**
     * @var string
     * @Flow\Validate(type="EmailAddress",validationGroups={"Controller","Default","validatedGroup"})
     */
    protected $emailAddress;

    /**
     * @var Collection<TestObjectArgument>
     * @Flow\Validate(type="Collection",validationGroups={"validatedGroup"})
     */
    protected $collection;

    /**
     * @var TestObjectArgument
     * @Flow\Validate(type="GenericObject",validationGroups={"validatedGroup"})
     */
    protected $related;

    public function __construct()
    {
        $this->collection = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param Collection<TestObjectArgument> $collection
     */
    public function setCollection(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @return Collection<TestObjectArgument>
     */
    public function getCollection()
    {
        return clone $this->collection;
    }

    /**
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @param string $emailAddress
     */
    public function setEmailAddress($emailAddress)
    {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @return TestObjectArgument
     */
    public function getRelated()
    {
        return $this->related;
    }

    /**
     * @param TestObjectArgument $related
     */
    public function setRelated($related)
    {
        $this->related = $related;
    }
}
