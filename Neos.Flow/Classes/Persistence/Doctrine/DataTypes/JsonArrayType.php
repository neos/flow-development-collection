<?php
namespace Neos\Flow\Persistence\Doctrine\DataTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonArrayType as DoctrineJsonArrayType;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Utility\TypeHandling;

/**
 * Extends the default doctrine JsonArrayType to work with entities.
 *
 * TODO: If doctrine supports a Postgres 9.4 platform we could default to jsonb.
 *
 * @Flow\Proxy(false)
 */
class JsonArrayType extends DoctrineJsonArrayType
{
    /**
     * @var string
     */
    const FLOW_JSON_ARRAY = 'flow_json_array';

    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName()
    {
        return self::FLOW_JSON_ARRAY;
    }

    /**
     * Gets the (preferred) binding type for values of this type that
     * can be used when binding parameters to prepared statements.
     *
     * @return integer
     */
    public function getBindingType()
    {
        return \PDO::PARAM_STR;
    }

    /**
     * Use jsonb for PostgreSQL, this means we require PostgreSQL 9.4
     *
     * @param array $fieldDeclaration The field declaration
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform The currently used database platform
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        switch ($platform->getName()) {
            case 'postgresql':
                return 'jsonb';
            default:
                return $platform->getJsonTypeDeclarationSQL($fieldDeclaration);
        }
    }

    /**
     * We map jsonb fields to our datatype by default. Doctrine doesn't use jsonb at all.
     *
     * @param AbstractPlatform $platform
     * @return array
     */
    public function getMappedDatabaseTypes(AbstractPlatform $platform)
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
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $this->initializeDependencies();

        switch ($platform->getName()) {
            case 'postgresql':
                $value = (is_resource($value)) ? stream_get_contents($value) : $value;
                $array = parent::convertToPHPValue($value, $platform);
                break;
            default:
                $array = parent::convertToPHPValue($value, $platform);
        }
        if (is_array($array)) {
            $this->decodeObjectReferences($array);
        }

        return $array;
    }

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param array $array The value to convert.
     * @param AbstractPlatform $platform The currently used database platform.
     * @return mixed The database representation of the value.
     */
    public function convertToDatabaseValue($array, AbstractPlatform $platform)
    {
        if ($array === null) {
            return null;
        }

        $this->initializeDependencies();
        $this->encodeObjectReferences($array);

        return json_encode($array, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Fetches dependencies from the static object manager.
     *
     * Injection cannot be used, since __construct on Types\Type is final.
     *
     * @return void
     */
    protected function initializeDependencies()
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
     * @param array $array
     * @return void
     */
    protected function decodeObjectReferences(array &$array)
    {
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
     * @param array $array
     * @return void
     * @throws \RuntimeException
     */
    protected function encodeObjectReferences(array &$array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->encodeObjectReferences($value);
            }
            if (!is_object($value) || (is_object($value) && $value instanceof DependencyProxy)) {
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
            } elseif ($value instanceof \Doctrine\Common\Collections\Collection) {
                throw new \RuntimeException('Collection in array properties is not supported', 1375196581);
            } elseif ($value instanceof \ArrayObject) {
                throw new \RuntimeException('ArrayObject in array properties is not supported', 1375196582);
            } elseif ($this->persistenceManager->isNewObject($value) === false
                && (
                    $this->reflectionService->isClassAnnotatedWith($propertyClassName, Flow\Entity::class)
                    || $this->reflectionService->isClassAnnotatedWith($propertyClassName, Flow\ValueObject::class)
                    || $this->reflectionService->isClassAnnotatedWith($propertyClassName, \Doctrine\ORM\Mapping\Entity::class)
                )
            ) {
                $value = [
                    '__flow_object_type' => $propertyClassName,
                    '__identifier' => $this->persistenceManager->getIdentifierByObject($value)
                ];
            }
        }
    }

    /**
     * We require a comment on the column to make doctrine recognize the type on already existing columns
     *
     * @return boolean
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
