<?php
namespace TYPO3\Flow\Persistence\Doctrine\DataTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types;
use TYPO3\Flow\Annotations as Flow;

/**
 * A datatype that replaces references to entities in arrays with a type/identifier tuple
 * and strips singletons from the data to be stored.
 */
class ObjectArray extends Types\ArrayType {

	/**
	 * @var string
	 */
	const OBJECTARRAY = 'objectarray';

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * Gets the name of this type.
	 *
	 * @return string
	 */
	public function getName() {
		return self::OBJECTARRAY;
	}

	/**
	 * Gets the SQL declaration snippet for a field of this type.
	 *
	 * @param array $fieldDeclaration
	 * @param AbstractPlatform $platform
	 * @return string
	 */
	public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform) {
		return $platform->getBlobTypeDeclarationSQL($fieldDeclaration);
	}

	/**
	 * Gets the (preferred) binding type for values of this type that
	 * can be used when binding parameters to prepared statements.
	 *
	 * @return integer
	 */
	public function getBindingType() {
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
	public function convertToPHPValue($value, AbstractPlatform $platform) {
		$this->initializeDependencies();

		$array = parent::convertToPHPValue($value, $platform);
		$this->decodeObjectReferences($array);

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
	public function convertToDatabaseValue($array, AbstractPlatform $platform) {
		$this->initializeDependencies();

		$this->encodeObjectReferences($array);

		return parent::convertToDatabaseValue($array, $platform);
	}

	/**
	 * Fetches dependencies from the static object manager.
	 *
	 * Injection cannot be used, since __construct on Types\Type is final.
	 *
	 * @return void
	 */
	protected function initializeDependencies() {
		if ($this->persistenceManager === NULL) {
			$this->persistenceManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface');
			$this->reflectionService = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Reflection\ReflectionService');
		}
	}

	/**
	 * Traverses the $array and replaces known persisted objects (tuples of
	 * type and identifier) with actual instances.
	 *
	 * @param array $array
	 * @return void
	 */
	protected function decodeObjectReferences(array &$array) {
		foreach ($array as &$value) {
			if (!is_array($value)) {
				continue;
			}

			if (isset($value['__flow_object_type'])) {
				$value = $this->persistenceManager->getObjectByIdentifier($value['__identifier'], $value['__flow_object_type']);
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
	protected function encodeObjectReferences(array &$array) {
		foreach ($array as &$value) {
			if (!is_object($value) || (is_object($value) && $value instanceof \TYPO3\Flow\Object\DependencyInjection\DependencyProxy)) {
				continue;
			}

			$propertyClassName = get_class($value);

			if ($value instanceof \SplObjectStorage) {
				throw new \RuntimeException('SplObjectStorage in array properties is not supported', 1375196580);
			} elseif ($value instanceof \Doctrine\Common\Collections\Collection) {
				throw new \RuntimeException('Collection in array properties is not supported', 1375196581);
			} elseif ($value instanceof \ArrayObject) {
				throw new \RuntimeException('ArrayObject in array properties is not supported', 1375196582);
			} elseif ($this->persistenceManager->isNewObject($value) === FALSE
				&& (
					$this->reflectionService->isClassAnnotatedWith($propertyClassName, 'TYPO3\Flow\Annotations\Entity')
					|| $this->reflectionService->isClassAnnotatedWith($propertyClassName, 'TYPO3\Flow\Annotations\ValueObject')
					|| $this->reflectionService->isClassAnnotatedWith($propertyClassName, 'Doctrine\ORM\Mapping\Entity')
				)
			) {
				$value = array(
					'__flow_object_type' => $propertyClassName,
					'__identifier' => $this->persistenceManager->getIdentifierByObject($value)
				);
			}
		}
	}
}
