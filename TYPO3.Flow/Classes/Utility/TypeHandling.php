<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Utility;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * PHP type handling functions
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TypeHandling {

	/**
	 * A property type parse pattern.
	 */
	const PARSE_TYPE_PATTERN = '/^\\\\?(?P<type>integer|int|float|double|boolean|bool|string|DateTime|[A-Z][a-zA-Z0-9\\\\]+|object|array|ArrayObject|SplObjectStorage|Doctrine\\\\Common\\\\Collections\\\\ArrayCollection)(?:<\\\\?(?P<elementType>[a-zA-Z0-9\\\\]+)>)?/';

	/**
	 * A type pattern to detect literal types.
	 */
	const LITERAL_TYPE_PATTERN = '/^(?:integer|int|float|double|boolean|bool|string)$/';

	/**
	 * @var array
	 */
	static $collectionTypes = array('array', 'ArrayObject', 'SplObjectStorage', 'Doctrine\Common\Collections\ArrayCollection');

	/**
	 * Returns an array with type information, including element type for
	 * collection types (array, SplObjectStorage, ...)
	 *
	 * @param string $type Type of the property (see PARSE_TYPE_PATTERN)
	 * @return array An array with information about the type
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function parseType($type) {
		$matches = array();
		if (preg_match(self::PARSE_TYPE_PATTERN, $type, $matches)) {
			$type = self::normalizeType($matches['type']);
			$elementType = isset($matches['elementType']) ? self::normalizeType($matches['elementType']) : NULL;

			if ($elementType !== NULL && !in_array($type, self::$collectionTypes)) {
				throw new \InvalidArgumentException('Type "' . $type . '" must not have an element type hint (' . $elementType . ').', 1264093642);
			}

			return array(
				'type' => $type,
				'elementType' => $elementType
			);
		} else {
			throw new \InvalidArgumentException('Invalid type encountered: ' . var_export($type, TRUE), 1264093630);
		}
	}

	/**
	 * Normalize data types so they match the PHP type names:
	 *  int -> integer
	 *  double -> float
	 *  bool -> boolean
	 *
	 * @param string $type Data type to unify
	 * @return string unified data type
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function normalizeType($type) {
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
	 * Returns TRUE if the $type is a literal.
	 *
	 * @param string $type
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function isLiteral($type) {
		return preg_match(self::LITERAL_TYPE_PATTERN, $type) === 1;
	}
}
?>
