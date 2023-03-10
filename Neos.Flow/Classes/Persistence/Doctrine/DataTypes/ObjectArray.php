<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\DataTypes;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Types;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\ORM\Mapping\Entity;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Utility\TypeHandling;

/**
 * A datatype that replaces references to entities in arrays with a type/identifier tuple
 * and strips singletons from the data to be stored.
 *
 * @Flow\Proxy(false)
 */
class ObjectArray extends Types\ArrayType
{
    private const OBJECTARRAY = 'objectarray';
    protected ?PersistenceManagerInterface $persistenceManager = null;
    protected ReflectionService $reflectionService;

    public function getName(): string
    {
        return self::OBJECTARRAY;
    }

    /**
     * Use a BLOB instead of CLOB
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getBlobTypeDeclarationSQL($column);
    }

    /**
     * Use LARGE_OBJECT instead of STRING
     */
    public function getBindingType(): int
    {
        return ParameterType::LARGE_OBJECT;
    }

    /**
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): array
    {
        $this->initializeDependencies();

        if ($platform instanceof PostgreSQL94Platform) {
            $value = hex2bin((is_resource($value)) ? stream_get_contents($value) : $value);
        }

        $value = parent::convertToPHPValue($value, $platform);
        $this->decodeObjectReferences($value);

        return $value;
    }

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException(sprintf('The ObjectArray type only converts arrays, %s given', gettype($value)), 1569945649);
        }

        $this->initializeDependencies();
        $this->encodeObjectReferences($value);

        if ($platform instanceof PostgreSQL94Platform) {
            return bin2hex(parent::convertToDatabaseValue($value, $platform));
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    /**
     * Fetches dependencies from the static object manager.
     *
     * Injection cannot be used, since __construct on Types\Type is final.
     */
    protected function initializeDependencies(): void
    {
        if ($this->persistenceManager === null) {
            $this->persistenceManager = Bootstrap::$staticObjectManager->get(PersistenceManagerInterface::class);
            $this->reflectionService = Bootstrap::$staticObjectManager->get(ReflectionService::class);
        }
    }

    /**
     * Traverses the $array and replaces known persisted objects (tuples of
     * type and identifier) with actual instances.
     */
    protected function decodeObjectReferences(array &$array): void
    {
        assert($this->persistenceManager instanceof PersistenceManagerInterface);

        foreach ($array as &$value) {
            if (!is_array($value)) {
                continue;
            }

            if (isset($value['__flow_object_type'])) {
                $value = $this->persistenceManager->getObjectByIdentifier($value['__identifier'], $value['__flow_object_type'], true);
            } else {
                $this->decodeObjectReferences($value);
            }
        }
    }

    /**
     * Traverses the $array and replaces known persisted objects with a tuple of
     * type and identifier.
     *
     * @throws \RuntimeException
     */
    protected function encodeObjectReferences(array &$array): void
    {
        assert($this->persistenceManager instanceof PersistenceManagerInterface);

        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->encodeObjectReferences($value);
            }
            if (!is_object($value) || ($value instanceof DependencyProxy)) {
                continue;
            }

            $propertyClassName = TypeHandling::getTypeForValue($value);

            if ($value instanceof \SplObjectStorage) {
                throw new \RuntimeException('SplObjectStorage in array properties is not supported', 1375196580);
            }

            if ($value instanceof Collection) {
                throw new \RuntimeException('Collection in array properties is not supported', 1375196581);
            }

            if ($value instanceof \ArrayObject) {
                throw new \RuntimeException('ArrayObject in array properties is not supported', 1375196582);
            }

            if ($this->persistenceManager->isNewObject($value) === false
                && (
                    $this->reflectionService->isClassAnnotatedWith($propertyClassName, Flow\Entity::class)
                    || $this->reflectionService->isClassAnnotatedWith($propertyClassName, Flow\ValueObject::class)
                    || $this->reflectionService->isClassAnnotatedWith($propertyClassName, Entity::class)
                )) {
                $value = [
                    '__flow_object_type' => $propertyClassName,
                    '__identifier' => $this->persistenceManager->getIdentifierByObject($value)
                ];
            }
        }
    }
}
