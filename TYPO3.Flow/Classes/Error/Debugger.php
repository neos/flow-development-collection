<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Error;

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
 * A debugging utility class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Debugger {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	static protected $objectManager;

	/**
	 *
	 * @var \SplObjectStorage
	 */
	static protected $renderedObjects;

	/**
	 * Hardcoded list of FLOW3 class names (regex) which should not be displayed during debugging
	 * @var array
	 */
	static protected $blacklistedClassNames = '/
		(F3\\\\FLOW3\\\\AOP.*)
		(F3\\\\FLOW3\\\\Cac.*) |
		(F3\\\\FLOW3\\\\Con.*) |
		(F3\\\\FLOW3\\\\Uti.*) |
		(F3\\\\FLOW3\\\\MVC\\\\Web\\\\Routing.*) |
		(F3\\\\FLOW3\\\\Log.*) |
		(F3\\\\FLOW3\\\\Obj.*) |
		(F3\\\\FLOW3\\\\Pac.*) |
		(F3\\\\FLOW3\\\\Per.*) |
		(F3\\\\FLOW3\\\\Pro.*) |
		(F3\\\\FLOW3\\\\Ref.*) |
		(F3\\\\FLOW3\\\\Sec.*) |
		(F3\\\\Fluid\\\\.*) |
		(PHPUnit_Framework_MockObject_InvocationMocker)
		/xs';

	/**
	 * Injects the Object Manager
	 *
	 * @param ObjectManagerInterface $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		self::$objectManager = $objectManager;
	}

	/**
	 * Clear the state of the debugger
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static public function clearState() {
		self::$renderedObjects = new \SplObjectStorage;
	}

	/**
	 * Renders a dump of the given variable
	 *
	 * @param mixed $variable
	 * @param integer $level
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static public function renderDump($variable, $level) {
		if ($level > 50) {
			return 'RECURSION ... ' . chr(10);
		}
		if (is_string($variable)) {
			$dump = sprintf('\'<span class="debug-string">%s</span>\' (%s)', htmlspecialchars((strlen($variable) > 2000) ? substr($variable, 0, 2000) . '…' : $variable), strlen($variable));
		} elseif (is_numeric($variable)) {
			$dump = sprintf('%s %s', gettype($variable), $variable);
		} elseif (is_array($variable)) {
			$dump = \F3\FLOW3\Error\Debugger::renderArrayDump($variable, $level + 1);
		} elseif (is_object($variable)) {
			$dump = \F3\FLOW3\Error\Debugger::renderObjectDump($variable, $level + 1);
		} elseif (is_bool($variable)) {
			$dump = $variable ? 'TRUE' : 'FALSE';
		} elseif (is_null($variable) || is_resource($variable)) {
			$dump = gettype($variable);
		}
		return $dump;
	}

	/**
	 * Renders a dump of the given array
	 *
	 * @param array $array
	 * @param integer $level
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function renderArrayDump($array, $level) {
		$type = is_array($array) ? 'array' : get_class($array);
		$dump = $type . (count($array) ? '(' . count($array) .')' . chr(10) : '(empty)');
		foreach ($array as $key => $value) {
			$dump .= str_repeat(' ', $level) . self::renderDump($key, 0) . ' => ';
			$dump .= self::renderDump($value, $level + 1) . chr(10);
		}
		return $dump;
	}

	/**
	 * Renders a dump of the given object
	 *
	 * @param object $object
	 * @param integer $level
	 * @param boolean $renderProperties
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function renderObjectDump($object, $level, $renderProperties = TRUE) {
		$dump = '';
		$scope = '';
		$additionalAttributes = '';

		if (self::$objectManager !== NULL) {
			$objectName = self::$objectManager->getObjectNameByClassName(get_class($object));
			if ($objectName !== FALSE) {
				switch(self::$objectManager->getScope($objectName)) {
					case \F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE :
						$scope = 'prototype';
						break;
					case \F3\FLOW3\Object\Configuration\Configuration::SCOPE_SINGLETON :
						$scope = 'singleton';
						break;
					case \F3\FLOW3\Object\Configuration\Configuration::SCOPE_SESSION :
						$scope = 'session';
						break;
				}
				if (self::$renderedObjects->contains($object)) {
					$renderProperties = FALSE;
				} elseif ($renderProperties === TRUE) {
					$scope .= '<a id="' . spl_object_hash($object) . '"></a>';
					self::$renderedObjects->attach($object);
				}
			} else {
				$additionalAttributes .= ' debug-unregistered';
			}
		}

		$className = ($object instanceof \F3\FLOW3\AOP\ProxyInterface) ? $object->FLOW3_AOP_Proxy_getProxyTargetClassName() : get_class($object);

		$dump .= '<span class="debug-object' . $additionalAttributes . '" title="' . spl_object_hash($object) . '">' . $className . '</span>';

		$dump .= ($scope !== '') ? '<span class="debug-scope">' . $scope .'</span>' : '';

		if ($object instanceof \F3\FLOW3\Persistence\Aspect\PersistenceMagicInterface) {
			if (property_exists($object, 'FLOW3_Persistence_Entity_UUID')) {
				$identifier = $object->FLOW3_Persistence_Entity_UUID;
				$persistenceType = 'entity';
			} elseif (property_exists($object, 'FLOW3_Persistence_ValueObject_Hash')) {
				$identifier = $object->FLOW3_Persistence_ValueObject_Hash;
				$persistenceType = 'value object';
			}
			$dump .= '<span class="debug-ptype" title="' . $identifier . '">' . $persistenceType . '</span>';
		}

		if ($object instanceof \F3\FLOW3\AOP\ProxyInterface) {
			$dump .= '<span class="debug-proxy" title="' . get_class($object) . '">proxy</span>';
		}

		if ($renderProperties === TRUE) {

			if ($object instanceof \SplObjectStorage) {
				$dump .= ' (' . (count($object) ?: 'empty') . ')' . chr(10);
				foreach ($object as $value) {
					$dump .= str_repeat(' ', $level);
					if (preg_match(self::$blacklistedClassNames, get_class($value)) !== 0) {
						$dump .= self::renderObjectDump($value, 0, FALSE) . '<span class="debug-filtered">filtered</span>' . chr(10);
					} else {
						$dump .= self::renderDump($value, $level + 1) . chr(10);
					}
				}
			} else {
				$classReflection = new \ReflectionClass($className);
				$dump .= chr(10);
				foreach ($classReflection->getProperties() as $property) {
					$dump .= str_repeat(' ', $level) . '<span class="debug-property">' . $property->getName() . '</span> => ';
					$property->setAccessible(TRUE);
					$value = $property->getValue($object);
					if (is_array($value)) {
						$dump .= self::renderDump($value, $level + 1) . chr(10);
					} elseif (is_object($value)) {
						if (preg_match(self::$blacklistedClassNames, get_class($value)) !== 0) {
							$dump .= self::renderObjectDump($value, 0, FALSE) . '<span class="debug-filtered">filtered</span>' . chr(10);
						} else {
							$dump .= self::renderDump($value, $level + 1) . chr(10);
						}
					} else {
						$dump .= self::renderDump($value, $level) . chr(10);
					}
				}
			}
		} elseif (self::$renderedObjects->contains($object)) {
			$dump = '<a href="#' . spl_object_hash($object) . '" class="debug-seeabove" title="see above">' . $dump . '</a>';
		}
		return $dump;
	}
}

namespace F3;

/**
 * A var_dump function optimized for FLOW3's object structures
 *
 * @param mixed $variable The variable to display a dump of
 * @param string $title optional custom title for the debug output
 * @return void
 * @author Robert Lemke <robert@typo3.org>
 * @author Bastian Waidelich <bastian@typo3.org>
 * @api
 */
function var_dump($variable, $title = NULL) {
	if ($title === NULL) {
		$title = 'FLOW3 Variable Dump';
	}
	\F3\FLOW3\Error\Debugger::clearState();
	echo '
		<link rel="stylesheet" type="text/css" href="/_Resources/Static/Packages/FLOW3/Error/Debugger.css" />
		<div class="F3-FLOW3-Error-Debugger-VarDump">
			<div class="F3-FLOW3-Error-Debugger-VarDump-Top">
				' . htmlspecialchars($title) . '
			</div>
			<div class="F3-FLOW3-Error-Debugger-VarDump-Center">
				<pre dir="ltr">' . \F3\FLOW3\Error\Debugger::renderDump($variable, 0) . '</pre>
			</div>
		</div>
	';
}

?>