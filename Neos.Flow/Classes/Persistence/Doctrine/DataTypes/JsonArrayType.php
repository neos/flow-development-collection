<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\DataTypes;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\ORM\Mapping\Entity as ORMEntity;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\Exception\TypeConverterException;
use Neos\Flow\Property\TypeConverter\DenormalizingObjectConverter;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Utility\TypeHandling;

/**
 * Extends the default doctrine JsonArrayType to work with entities.
 *
 * TODO: If doctrine supports a Postgres 9.4 platform we could default to jsonb.
 *
 * @Flow\Proxy(false)
 */
class JsonArrayType extends JsonType
{
    const FLOW_JSON_ARRAY = 'flow_json_array';
    protected ?PersistenceManagerInterface $persistenceManager = null;
    protected ReflectionService $reflectionService;

    public function getName(): string
    {
        return self::FLOW_JSON_ARRAY;
    }

    /**
     * Use jsonb for PostgreSQL.
     *
     * The `json` format, is not comparable in PostgreSQL, something that
     * leads to issues if you want to use `DISTINCT` in a query.
     * Starting with PostgreSQL 9.4 the `jsonb` type is available, and the
     * DB knows how to compare it, making distinct queries possible.
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if ($platform instanceof PostgreSQL94Platform) {
            return 'jsonb';
        }

        return $platform->getJsonTypeDeclarationSQL($column);
    }

    /**
     * We map jsonb fields to our datatype by default. Doctrine doesn't use jsonb at all.
     */
    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return ['jsonb'];
    }

    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param mixed $value The value to convert.
     * @param AbstractPlatform $platform The currently used database platform.
     * @return array The PHP representation of the value.
     * @throws ConversionException
     * @throws TypeConverterException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): array
    {
        $value = parent::convertToPHPValue($value, $platform);

        if (!is_array($value)) {
            throw new \InvalidArgumentException(sprintf('The JsonArrayType only converts arrays, %s given', get_debug_type($value)), 1663056939);
        }

        $this->initializeDependencies();
        $this->decodeObjectReferences($value);

        return $value;
    }

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed $value The value to convert.
     * @param AbstractPlatform $platform The currently used database platform.
     * @return string|bool|null The database representation of the value.
     * @throws \JsonException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            throw new \InvalidArgumentException(sprintf('The JsonArrayType only converts arrays, %s given', gettype($value)), 1569944963);
        }

        $this->initializeDependencies();
        $this->encodeObjectReferences($value);

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE);
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
     *
     * @throws TypeConverterException
     */
    protected function decodeObjectReferences(array &$array): void
    {
        assert($this->persistenceManager instanceof PersistenceManagerInterface);

        foreach ($array as &$value) {
            if (!is_array($value)) {
                continue;
            }

            if (isset($value['__value_object_value'], $value['__value_object_type'])) {
                $value = self::deserializeValueObject($value);
            } elseif (isset($value['__flow_object_type'])) {
                $value = $this->persistenceManager->getObjectByIdentifier($value['__identifier'], $value['__flow_object_type'], true);
            } else {
                $this->decodeObjectReferences($value);
            }
        }
    }

    /**
     * @throws \InvalidArgumentException
     * @throws TypeConverterException
     */
    public static function deserializeValueObject(array $serializedValueObject): \JsonSerializable
    {
        if (isset($serializedValueObject['__value_object_value'], $serializedValueObject['__value_object_type'])) {
            return DenormalizingObjectConverter::convertFromSource(
                $serializedValueObject['__value_object_value'],
                $serializedValueObject['__value_object_type']
            );
        }

        throw new \InvalidArgumentException(
            '$serializedValueObject must contain keys "__value_object_value" and "__value_object_type"',
            1621332088
        );
    }

    /**
     * Traverses the $array and replaces known persisted objects with a tuple of
     * type and identifier.
     *
     * @throws \RuntimeException
     * @throws \JsonException
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

            if ($value instanceof \DateTimeInterface) {
                $value = [
                    'date' => $value->format('Y-m-d H:i:s.u'),
                    'timezone' => $value->format('e'),
                    'dateFormat' => 'Y-m-d H:i:s.u'
                ];
            } elseif ($value instanceof \SplObjectStorage) {
                throw new \RuntimeException('SplObjectStorage in array properties is not supported', 1375196580);
            } elseif ($value instanceof Collection) {
                throw new \RuntimeException('Collection in array properties is not supported', 1375196581);
            } elseif ($value instanceof \ArrayObject) {
                throw new \RuntimeException('ArrayObject in array properties is not supported', 1375196582);
            } elseif ($this->persistenceManager->isNewObject($value) === false
                && (
                    $this->reflectionService->isClassAnnotatedWith($propertyClassName, Flow\Entity::class)
                    || $this->reflectionService->isClassAnnotatedWith($propertyClassName, Flow\ValueObject::class)
                    || $this->reflectionService->isClassAnnotatedWith($propertyClassName, ORMEntity::class)
                )
            ) {
                $value = [
                    '__flow_object_type' => $propertyClassName,
                    '__identifier' => $this->persistenceManager->getIdentifierByObject($value)
                ];
            } elseif ($value instanceof \JsonSerializable
                && DenormalizingObjectConverter::isDenormalizable(get_class($value))
            ) {
                $value = self::serializeValueObject($value);
            }
        }
    }

    /**
     * @throws \RuntimeException
     * @throws \JsonException
     */
    public static function serializeValueObject(\JsonSerializable $valueObject): array
    {
        if ($json = json_encode($valueObject, JSON_THROW_ON_ERROR)) {
            return [
                '__value_object_type' => get_class($valueObject),
                '__value_object_value' =>
                    json_decode($json, true, 512, JSON_THROW_ON_ERROR)
            ];
        }

        throw new \RuntimeException(
            sprintf(
                'Could not serialize $valueObject due to: %s',
                json_last_error_msg()
            ),
            1621333154
        );
    }

    /**
     * We require a comment on the column to make doctrine recognize the type on already existing columns
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
