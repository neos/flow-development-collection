<?php
namespace Neos\Utility;

/*
 * This file is part of the Neos.Utility.ObjectHandling package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\Proxy;
use Neos\Utility\Exception\InvalidTypeException;

/**
 * PHP type handling functions
 *
 */
abstract class TypeHandling
{
    /**
     * A property type parse pattern.
     */
    const PARSE_TYPE_PATTERN = '/^\??(?:null\|)?\\\\?(?P<type>[a-zA-Z0-9\\\\_]+)(?:<\\\\?(?P<elementType>[a-zA-Z0-9\\\\_]+)>)?(?:\|null)?(?:\s|$)/';

    /**
     * A type pattern to detect literal types.
     */
    const LITERAL_TYPE_PATTERN = '/^(?:integer|int|float|double|boolean|bool|string)$/';

    /**
     * @var array
     */
    protected static $collectionTypes = ['array', \Traversable::class];

    /**
     * Returns an array with type information, including element type for
     * collection types (array, SplObjectStorage, ...)
     *
     * @param string $type Type of the property (see PARSE_TYPE_PATTERN)
     * @return array An array with information about the type
     * @throws InvalidTypeException
     */
    public static function parseType(string $type): array
    {
        $matches = [];
        if (preg_match(self::PARSE_TYPE_PATTERN, $type, $matches) === 0) {
            throw new InvalidTypeException('Found an invalid element type declaration. A type "' . var_export($type, true) . '" does not exist.', 1264093630);
        }

        $typeWithoutNull = self::stripNullableType($type);
        $isNullable = $typeWithoutNull !== $type || $type === 'null';
        $type = self::normalizeType($matches['type']);
        $elementType = isset($matches['elementType']) ? self::normalizeType($matches['elementType']) : null;

        if ($elementType !== null && !self::isCollectionType($type)) {
            throw new InvalidTypeException('Found an invalid element type declaration. Type "' . $type . '" must not have an element type hint (' . $elementType . ').', 1264093642);
        }

        return [
            'type' => $type,
            'elementType' => $elementType,
            'nullable' => $isNullable
        ];
    }

    /**
     * Normalize data types so they match the PHP type names:
     *  int -> integer
     *  double -> float
     *  bool -> boolean
     *
     * @param string $type Data type to unify
     * @return string unified data type
     */
    public static function normalizeType(string $type): string
    {
        switch ($type) {
            case 'int':
                $type = 'integer';
            break;
            case 'bool':
                $type = 'boolean';
            break;
            case 'double':
                $type = 'float';
            break;
        }
        return $type;
    }

    /**
     * Returns true if the $type is a literal.
     *
     * @param string $type
     * @return boolean
     */
    public static function isLiteral(string $type): bool
    {
        return preg_match(self::LITERAL_TYPE_PATTERN, $type) === 1;
    }

    /**
     * Returns true if the $type is a simple type.
     *
     * @param string $type
     * @return boolean
     */
    public static function isSimpleType(string $type): bool
    {
        return in_array(self::normalizeType($type), ['array', 'string', 'float', 'integer', 'boolean'], true);
    }

    /**
     * Returns true if the $type is a collection type.
     *
     * @param string $type
     * @return boolean
     */
    public static function isCollectionType(string $type): bool
    {
        if (in_array($type, self::$collectionTypes, true)) {
            return true;
        }

        if (class_exists($type) === true || interface_exists($type) === true) {
            foreach (self::$collectionTypes as $collectionType) {
                if (is_subclass_of($type, $collectionType) === true) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Parses a composite type like "\Foo\Collection<\Bar\Entity>" into "\Foo\Collection"
     * Note: If the given type does not specify an element type it is not changed
     *
     * @param string $type
     * @return string The original type without its element type (if any)
     */
    public static function truncateElementType(string $type): string
    {
        if (strpos($type, '<') === false) {
            return $type;
        }
        return substr($type, 0, strpos($type, '<'));
    }

    /**
     * @param string $type
     * @return string The original type without an optional "null" type information
     */
    public static function stripNullableType($type)
    {
        if ($type[0] === '?') {
            $type = substr($type, 1);
        }
        if (stripos($type, 'null') === false) {
            return $type;
        }
        return preg_replace('/(\\|null|null\\|)/i', '', $type);
    }

    /**
     * Return simple type or class for object
     *
     * @param mixed $value
     * @return string
     */
    public static function getTypeForValue($value): string
    {
        if (is_object($value)) {
            if ($value instanceof Proxy) {
                $type = get_parent_class($value);
            } else {
                $type = get_class($value);
            }
        } else {
            $type = gettype($value);
        }
        return $type;
    }
}
