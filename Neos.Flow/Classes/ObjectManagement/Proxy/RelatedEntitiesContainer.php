<?php

namespace Neos\Flow\ObjectManagement\Proxy;

use Doctrine\Persistence\Proxy as DoctrineProxy;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\ObjectAccess;

/**
 * This is a mutable container to hold references to entities (class & identifier) for serialization.
 * Userland code should never (have to) interact with this, it is used in proxy classes only. You might
 * see references to it in serialized object strings.
 *
 * @phpstan-type RelatedEntityShape array{n:string, c:false|string, i:mixed, p:string}
 * @implements \IteratorAggregate<int, RelatedEntityShape>
 * @internal
 */
#[Flow\Proxy(false)]
final class RelatedEntitiesContainer implements \IteratorAggregate
{
    /**
     * @var array<string, RelatedEntityShape>
     */
    protected array $e = [];

    public function getIterator(): \Generator
    {
        foreach ($this->e as $entityInformation) {
            yield $entityInformation;
        }
    }

    public function reset(): void
    {
        $this->e = [];
    }

    public function appendRelatedEntity(string $originalPropertyName, string $path, object $propertyValue): void
    {
        if ($propertyValue instanceof DoctrineProxy) {
            $className = get_parent_class($propertyValue);
        } else {
            $className = Bootstrap::$staticObjectManager->getObjectNameByClassName(get_class($propertyValue));
        }
        $identifier = Bootstrap::$staticObjectManager->get(PersistenceManagerInterface::class)->getIdentifierByObject($propertyValue);
        if (!$identifier && $propertyValue instanceof DoctrineProxy) {
            $identifier = current(ObjectAccess::getProperty($propertyValue, '_identifier', true));
        }

        $this->e[$originalPropertyName . '.' . $path] = [
            'n' => $originalPropertyName,
            'c' => $className,
            'i' => $identifier,
            'p' => $path
        ];
    }
}
