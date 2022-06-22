<?php
namespace Neos\Flow\Persistence\Doctrine\DataTypes;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types;
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
    /**
     * @var string
     */
    const OBJECTARRAY = 'objectarray';

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
        return self::OBJECTARRAY;
    }

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array $fieldDeclaration
     * @param AbstractPlatform $platform
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getBlobTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * Gets the (preferred) binding type for values of this type that
     * can be used when binding parameters to prepared statements.
     *
     * @return integer
     */
    public function getBindingType()
    {
        return \PDO::PARAM_LOB;
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
                $array = parent::convertToPHPValue(hex2bin($value), $platform);
            break;
            default:
                $array = parent::convertToPHPValue($value, $platform);
        }
        $this->decodeObjectReferences($array);

        return $array;
    }

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed $array The value to convert.
     * @param AbstractPlatform $platform The currently used database platform.
     * @return mixed The database representation of the value.
     */
    public function convertToDatabaseValue($array, AbstractPlatform $platform)
    {
        if (!is_array($array)) {
            throw new \InvalidArgumentException(sprintf('The ObjectArray type only converts arrays, %s given', gettype($array)), 1569945649);
        }

        $this->initializeDependencies();

        $this->encodeObjectReferences($array);

        switch ($platform->getName()) {
            case 'postgresql':
                return bin2hex(parent::convertToDatabaseValue($array, $platform));
            default:
                return parent::convertToDatabaseValue($array, $platform);
        }
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
