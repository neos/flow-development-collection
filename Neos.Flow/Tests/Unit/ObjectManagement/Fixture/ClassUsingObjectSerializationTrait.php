<?php

namespace Neos\Flow\Tests\Unit\ObjectManagement\Fixture;

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
use Neos\Flow\ObjectManagement\Proxy\ObjectSerializationTrait;
use Neos\Flow\ObjectManagement\Proxy\RelatedEntitiesContainer;

/**
 * A stub which uses the ObjectSerializationTrait and mimics the properties a generated
 * proxy class would provide. The private trait methods are exposed through public methods
 * so that they can be called from a test case.
 */
class ClassUsingObjectSerializationTrait
{
    use ObjectSerializationTrait;

    public static string $staticProperty = 'a static value';
    public string $simpleProperty = 'a simple value';
    public string $transientProperty = 'a transient value';
    public string $injectedProperty = 'an injected value';
    public array $arrayProperty = [];
    public Collection $collectionProperty;
    public ?object $objectProperty = null;
    public ?SomeImplementation $typedObjectProperty = null;
    public ?SomeEntity $entityProperty = null;

    public array $Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = [];
    public array $Flow_Aop_Proxy_groupedAdviceChains = [];
    public array $Flow_Aop_Proxy_methodIsInAdviceMode = [];
    public array $Flow_Injected_Properties = ['injectedProperty'];
    public ?RelatedEntitiesContainer $Flow_Persistence_RelatedEntitiesContainer = null;

    public function __construct()
    {
        $this->collectionProperty = new ArrayCollection();
    }

    /**
     * @return string[]
     */
    public function serializeRelatedEntities(array $transientProperties = [], array $propertyVarTags = []): array
    {
        return $this->Flow_serializeRelatedEntities($transientProperties, $propertyVarTags);
    }

    public function setRelatedEntities(): void
    {
        $this->Flow_setRelatedEntities();
    }
}
