<?php
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
	 * Is set to TRUE once the CSS file is included in the current page to prevent double inclusions of the CSS file.
	 * @var boolean
	 */
	static public $stylesheetEchoed = FALSE;

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
	 * @param boolean $plaintext
	 * @param boolean $ansiColors
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	static public function renderDump($variable, $level, $plaintext = FALSE, $ansiColors = FALSE) {
		if ($level > 50) {
			return 'RECURSION ... ' . chr(10);
		}
		if (is_string($variable)) {
			$croppedValue = (strlen($variable) > 2000) ? substr($variable, 0, 2000) . '…' : $variable;
			if ($plaintext) {
				$dump = 'string ' . self::ansiEscapeWrap('"' . $croppedValue . '"', '33', $ansiColors) . ' (' . strlen($variable) . ')';
			} else {
				$dump = sprintf('\'<span class="debug-string">%s</span>\' (%s)', htmlspecialchars($croppedValue), strlen($variable));
			}
		} elseif (is_numeric($variable)) {
			$dump = sprintf('%s %s', gettype($variable), self::ansiEscapeWrap($variable, '35', $ansiColors));
		} elseif (is_array($variable)) {
			$dump = \F3\FLOW3\Error\Debugger::renderArrayDump($variable, $level + 1, $plaintext, $ansiColors);
		} elseif (is_object($variable)) {
			$dump = \F3\FLOW3\Error\Debugger::renderObjectDump($variable, $level + 1, TRUE, $plaintext, $ansiColors);
		} elseif (is_bool($variable)) {
			$dump = $variable ? self::ansiEscapeWrap('TRUE', '32', $ansiColors) : self::ansiEscapeWrap('FALSE', '31', $ansiColors);
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
	 * @param boolean $plaintext
	 * @param boolean $ansiColors
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	static protected function renderArrayDump($array, $level, $plaintext = FALSE, $ansiColors = FALSE) {
		$type = is_array($array) ? 'array' : get_class($array);
		$dump = $type . (count($array) ? '(' . count($array) .')' : '(empty)');
		foreach ($array as $key => $value) {
			$dump .= chr(10) . str_repeat(' ', $level) . self::renderDump($key, 0, $plaintext, $ansiColors) . ' => ';
			$dump .= self::renderDump($value, $level + 1, $plaintext, $ansiColors);
		}
		return $dump;
	}

	/**
	 * Renders a dump of the given object
	 *
	 * @param object $object
	 * @param integer $level
	 * @param boolean $renderProperties
	 * @param boolean $plaintext
	 * @param boolean $ansiColors
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	static protected function renderObjectDump($object, $level, $renderProperties = TRUE, $plaintext = FALSE, $ansiColors = FALSE) {
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
					if (!$plaintext) {
						$scope .= '<a id="' . spl_object_hash($object) . '"></a>';
					}
					self::$renderedObjects->attach($object);
				}
			} else {
				$additionalAttributes .= ' debug-unregistered';
			}
		}

		$className = get_class($object);

		if ($plaintext) {
			$dump .= $className;
		} else {
			$dump .= '<span class="debug-object' . $additionalAttributes . '" title="' . spl_object_hash($object) . '">' . $className . '</span>';
		}

		if ($plaintext) {
			$dump .= ($scope !== '') ? ' ' . self::ansiEscapeWrap($scope, '44;37', $ansiColors) : '';
		} else {
			$dump .= ($scope !== '') ? '<span class="debug-scope">' . $scope .'</span>' : '';
		}

		if (property_exists($object, 'FLOW3_Persistence_Identifier')) {
			$identifier = \F3\FLOW3\Reflection\ObjectAccess::getProperty($object, 'FLOW3_Persistence_Identifier', TRUE);
			$persistenceType = 'entity or value object (FIXME)';
		} else {
			$identifier = 'unknown';
			$persistenceType = 'object';
		}
		if ($plaintext) {
			$dump .= ' ' . self::ansiEscapeWrap($persistenceType, '42;37', $ansiColors);
		} else {
			$dump .= '<span class="debug-ptype" title="' . $identifier . '">' . $persistenceType . '</span>';
		}

		if ($object instanceof \F3\FLOW3\Object\Proxy\ProxyInterface) {
			if ($plaintext) {
				$dump .= ' ' . self::ansiEscapeWrap('proxy', '41;37', $ansiColors);
			} else {
				$dump .= '<span class="debug-proxy" title="' . get_class($object) . '">proxy</span>';
			}
		}

		if ($renderProperties === TRUE) {
			if ($object instanceof \SplObjectStorage) {
				$dump .= ' (' . (count($object) ?: 'empty') . ')';
				foreach ($object as $value) {
					$dump .= chr(10);
					$dump .= str_repeat(' ', $level);
					if (preg_match(self::$blacklistedClassNames, get_class($value)) !== 0) {
						$dump .= self::renderObjectDump($value, 0, FALSE, $plaintext, $ansiColors);
						if ($plaintext) {
							$dump .= ' ' . self::ansiEscapeWrap('filtered', '47;30', $ansiColors);
						} else {
							$dump .= '<span class="debug-filtered">filtered</span>';
						}
					} else {
						$dump .= self::renderDump($value, $level + 1, $plaintext, $ansiColors);
					}
				}
			} else {
				$classReflection = new \ReflectionClass($className);
				$properties = $classReflection->getProperties();
				foreach ($properties as $property) {
					$dump .= chr(10);
					$dump .= str_repeat(' ', $level) . ($plaintext ? '' : '<span class="debug-property">') . self::ansiEscapeWrap($property->getName(), '36', $ansiColors) . ($plaintext ? '' : '</span>') . ' => ';
					$property->setAccessible(TRUE);
					$value = $property->getValue($object);
					if (is_array($value)) {
						$dump .= self::renderDump($value, $level + 1, $plaintext, $ansiColors);
					} elseif (is_object($value)) {
						if (preg_match(self::$blacklistedClassNames, get_class($value)) !== 0) {
							$dump .= self::renderObjectDump($value, 0, FALSE, $plaintext, $ansiColors) . ($plaintext ? ' ' . self::ansiEscapeWrap('filtered', '47;30', $ansiColors) : '<span class="debug-filtered">filtered</span>');
						} else {
							$dump .= self::renderDump($value, $level + 1, $plaintext, $ansiColors);
						}
					} else {
						$dump .= self::renderDump($value, $level, $plaintext, $ansiColors);
					}
				}
			}
		} elseif (self::$renderedObjects->contains($object)) {
			if (!$plaintext) {
				$dump = '<a href="#' . spl_object_hash($object) . '" class="debug-seeabove" title="see above">' . $dump . '</a>';
			}
		}
		return $dump;
	}

	/**
	 * Wrap a string with the ANSI escape sequence for colorful output
	 *
	 * @param string $string The string to wrap
	 * @param string $ansiColors The ansi color sequence (e.g. "1;37")
	 * @param boolean $enable If FALSE, the raw string will be returned
	 * @return string The wrapped or raw string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	static protected function ansiEscapeWrap($string, $ansiColors, $enable = TRUE) {
		if ($enable) {
			return "\x1B[" . $ansiColors . 'm' . $string . "\x1B[0m";
		} else {
			return $string;
		}
	}
}

namespace F3;

/**
 * A var_dump function optimized for FLOW3's object structures
 *
 * @param mixed $variable The variable to display a dump of
 * @param string $title optional custom title for the debug output
 * @param boolean $return if TRUE, the dump is returned for displaying it embedded in custom HTML. If FALSE (default), the variable dump is directly displayed.
 * @param boolean $plaintext If TRUE, the dump is in plain text, if FALSE the debug output is in HTML format. If not specified, the mode is guessed from FLOW3_SAPITYPE
 * @return void/string if $return is TRUE, the variable dump is returned. By default, the dump is directly displayed, and nothing is returned.
 * @author Robert Lemke <robert@typo3.org>
 * @author Bastian Waidelich <bastian@typo3.org>
 * @author Sebastian Kurfürst <sebastian@typo3.org>
 * @api
 */
function var_dump($variable, $title = NULL, $return = FALSE, $plaintext = NULL) {
	if ($plaintext === NULL) {
		$plaintext = (FLOW3_SAPITYPE === 'CLI');
		$ansiColors = $plaintext;
	} else {
		$ansiColors = FALSE;
	}

	if ($title === NULL) {
		$title = 'FLOW3 Variable Dump';
	}
	if ($ansiColors) {
		$title = "\x1B[1m" . $title . "\x1B[0m";
	}
	\F3\FLOW3\Error\Debugger::clearState();

	if (!$plaintext && \F3\FLOW3\Error\Debugger::$stylesheetEchoed === FALSE) {
		echo '<link rel="stylesheet" type="text/css" href="/_Resources/Static/Packages/FLOW3/Error/Debugger.css" />';
		\F3\FLOW3\Error\Debugger::$stylesheetEchoed = TRUE;
	}

	if ($plaintext) {
		$output = $title . chr(10) . \F3\FLOW3\Error\Debugger::renderDump($variable, 0, TRUE, $ansiColors) . chr(10) . chr(10);
	} else {
		$output = '
			<div class="F3-FLOW3-Error-Debugger-VarDump ' . ($return ? 'F3-FLOW3-Error-Debugger-VarDump-Inline' : 'F3-FLOW3-Error-Debugger-VarDump-Floating') . '">
				<div class="F3-FLOW3-Error-Debugger-VarDump-Top">
					' . htmlspecialchars($title) . '
				</div>
				<div class="F3-FLOW3-Error-Debugger-VarDump-Center">
					<pre dir="ltr">' . \F3\FLOW3\Error\Debugger::renderDump($variable, 0, FALSE, FALSE) . '</pre>
				</div>
			</div>
		';
	}

	if ($return === TRUE) {
		return $output;
	} else {
		echo $output;
	}
}

?>
