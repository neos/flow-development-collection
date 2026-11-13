<?php

declare(strict_types=1);

namespace Neos\Flow\Mvc\Routing;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Exception\UnknownObjectException;
use Neos\Flow\Persistence\PersistenceManagerInterface;

/**
 * Normalizer to convert flow entities in route values to its identity
 *
 * The identity will be converted back to the object via the
 * property mapper {@see \Neos\Flow\Property\TypeConverter\PersistentObjectConverter} in the action controller
 *
 * @internal
 */
#[Flow\Scope('singleton')]
final readonly class FlowPersistenceRouteValuesNormalizer implements RouteValuesNormalizerInterface
{
    public function __construct(
        private PersistenceManagerInterface $persistenceManager
    ) {
    }

    /**
     * Recursively iterates through the given array and turns objects
     * into an arrays containing the identity of the domain object.
     *
     * @param array<mixed> $array The array to be iterated over
     * @return array<mixed> The modified array without objects
     * @throws UnknownObjectException if array contains objects that are not known to the Persistence Manager
     */
    public function normalizeObjects(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->normalizeObjects($value);
            } elseif (is_object($value) && $value instanceof \Traversable) {
                $array[$key] = $this->normalizeObjects(iterator_to_array($value));
            } elseif (is_object($value)) {
                $array[$key] = $this->normalizeObject($value);
            }
        }
        return $array;
    }

    /**
     * Converts the given object into an array containing the identity of the domain object.
     *
     * @param object $object The object to be converted
     * @return array{__identity: string} The identity array in the format array('__identity' => '...')
     * @throws UnknownObjectException if the given object is not known to the Persistence Manager
     */
    private function normalizeObject(object $object): array
    {
        $identifier = $this->persistenceManager->getIdentifierByObject($object);
        if ($identifier === null) {
            throw new UnknownObjectException(sprintf('Tried to convert an object of type "%s" to an identity array, but it is unknown to the Persistence Manager.', $object::class), 1740046025);
        }
        return ['__identity' => $identifier];
    }
}
