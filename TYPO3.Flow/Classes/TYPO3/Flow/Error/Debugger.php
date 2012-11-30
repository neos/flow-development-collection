<?php
namespace TYPO3\Flow\Error;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * A debugging utility class
 *
 * @Flow\Proxy(false)
 */
class Debugger {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	static protected $objectManager;

	/**
	 *
	 * @var array
	 */
	static protected $renderedObjects = array();

	/**
	 * Hardcoded list of Flow class names (regex) which should not be displayed during debugging
	 * @var array
	 */
	static protected $blacklistedClassNames = '/
		(TYPO3\\\\Flow\\\\Aop.*)
		(TYPO3\\\\Flow\\\\Cac.*) |
		(TYPO3\\\\Flow\\\\Core\\\\.*) |
		(TYPO3\\\\Flow\\\\Con.*) |
		(TYPO3\\\\Flow\\\\Http\\\\RequestHandler) |
		(TYPO3\\\\Flow\\\\Uti.*) |
		(TYPO3\\\\Flow\\\\Mvc\\\\Routing.*) |
		(TYPO3\\\\Flow\\\\Log.*) |
		(TYPO3\\\\Flow\\\\Obj.*) |
		(TYPO3\\\\Flow\\\\Pac.*) |
		(TYPO3\\\\Flow\\\\Persistence\\\\(?!Doctrine\\\\Mapping).*) |
		(TYPO3\\\\Flow\\\\Pro.*) |
		(TYPO3\\\\Flow\\\\Ref.*) |
		(TYPO3\\\\Flow\\\\Sec.*) |
		(TYPO3\\\\Flow\\\\Sig.*) |
		(TYPO3\\\\Fluid\\\\.*) |
		(PHPUnit_Framework_MockObject_InvocationMocker)
		/xs';

	static protected $blacklistedPropertyNames = '/
		(Flow_Aop_.*)
		/xs';

	/**
	 * Is set to TRUE once the CSS file is included in the current page to prevent double inclusions of the CSS file.
	 * @var boolean
	 */
	static public $stylesheetEchoed = FALSE;

	/**
	 * Injects the Object Manager
	 *
	 * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	static public function injectObjectManager(\TYPO3\Flow\Object\ObjectManagerInterface $objectManager) {
		self::$objectManager = $objectManager;
	}

	/**
	 * Clear the state of the debugger
	 *
	 * @return void
	 */
	static public function clearState() {
		self::$renderedObjects = array();
	}

	/**
	 * Renders a dump of the given variable
	 *
	 * @param mixed $variable
	 * @param integer $level
	 * @param boolean $plaintext
	 * @param boolean $ansiColors
	 * @return string
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
			$dump = \TYPO3\Flow\Error\Debugger::renderArrayDump($variable, $level + 1, $plaintext, $ansiColors);
		} elseif (is_object($variable)) {
			$dump = \TYPO3\Flow\Error\Debugger::renderObjectDump($variable, $level + 1, TRUE, $plaintext, $ansiColors);
		} elseif (is_bool($variable)) {
			$dump = $variable ? self::ansiEscapeWrap('TRUE', '32', $ansiColors) : self::ansiEscapeWrap('FALSE', '31', $ansiColors);
		} elseif (is_null($variable) || is_resource($variable)) {
			$dump = gettype($variable);
		} else {
			$dump = '[unhandled type]';
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
	 */
	static protected function renderObjectDump($object, $level, $renderProperties = TRUE, $plaintext = FALSE, $ansiColors = FALSE) {
		$dump = '';
		$scope = '';
		$additionalAttributes = '';

		if ($object instanceof \Doctrine\Common\Collections\Collection) {
			return self::renderArrayDump(\Doctrine\Common\Util\Debug::export($object, 12), $level, $plaintext, $ansiColors);
		}

			// Objects returned from Doctrine's Debug::export function are stdClass with special properties:
		try {
			$objectIdentifier = ObjectAccess::getProperty($object, 'Persistence_Object_Identifier', TRUE);
		} catch (\TYPO3\Flow\Reflection\Exception\PropertyNotAccessibleException $exception) {
			$objectIdentifier = spl_object_hash($object);
		}
		$className = ($object instanceof \stdClass && isset($object->__CLASS__)) ? $object->__CLASS__ : get_class($object);

		if (preg_match(self::$blacklistedClassNames, $className) !== 0 || isset(self::$renderedObjects[$objectIdentifier])) {
			$renderProperties = FALSE;
		}
		self::$renderedObjects[$objectIdentifier] = TRUE;

		if (self::$objectManager !== NULL) {
			$objectName = self::$objectManager->getObjectNameByClassName(get_class($object));
			if ($objectName !== FALSE) {
				switch(self::$objectManager->getScope($objectName)) {
					case \TYPO3\Flow\Object\Configuration\Configuration::SCOPE_PROTOTYPE :
						$scope = 'prototype';
						break;
					case \TYPO3\Flow\Object\Configuration\Configuration::SCOPE_SINGLETON :
						$scope = 'singleton';
						break;
					case \TYPO3\Flow\Object\Configuration\Configuration::SCOPE_SESSION :
						$scope = 'session';
						break;
				}
			} else {
				$additionalAttributes .= ' debug-unregistered';
			}
		}

		if ($renderProperties === TRUE && !$plaintext) {
			if ($scope === '') {
				$scope = 'prototype';
			}
			$scope .= '<a id="o' . $objectIdentifier . '"></a>';
		}

		if ($plaintext) {
			$dump .= $className;
			$dump .= ($scope !== '') ? ' ' . self::ansiEscapeWrap($scope, '44;37', $ansiColors) : '';
		} else {
			$dump .= '<span class="debug-object' . $additionalAttributes . '" title="' . $objectIdentifier . '">' . $className . '</span>';
			$dump .= ($scope !== '') ? '<span class="debug-scope">' . $scope .'</span>' : '';
		}

		if (property_exists($object, 'Persistence_Object_Identifier')) {
			$persistenceIdentifier = $objectIdentifier;
			$persistenceType = 'persistable';
		} elseif ($object instanceof \Closure) {
			$persistenceIdentifier = 'n/a';
			$persistenceType = 'closure';
		} else {
			$persistenceIdentifier = 'unknown';
			$persistenceType = 'object';
		}

		if ($plaintext) {
			$dump .= ' ' . self::ansiEscapeWrap($persistenceType, '42;37', $ansiColors);
		} else {
			$dump .= '<span class="debug-ptype" title="' . $persistenceIdentifier . '">' . $persistenceType . '</span>';
		}

		if ($object instanceof \TYPO3\Flow\Object\Proxy\ProxyInterface || (property_exists($object, '__IS_PROXY__') && $object->__IS_PROXY__ === TRUE)) {
			if ($plaintext) {
				$dump .= ' ' . self::ansiEscapeWrap('proxy', '41;37', $ansiColors);
			} else {
				$dump .= '<span class="debug-proxy" title="' . $className . '">proxy</span>';
			}
		}

		if ($renderProperties === TRUE) {
			if ($object instanceof \SplObjectStorage) {
				$dump .= ' (' . (count($object) ?: 'empty') . ')';
				foreach ($object as $value) {
					$dump .= chr(10);
					$dump .= str_repeat(' ', $level);
					$dump .= self::renderObjectDump($value, 0, FALSE, $plaintext, $ansiColors);
				}
			} else {
				$classReflection = new \ReflectionClass($className);
				$properties = $classReflection->getProperties();
				foreach ($properties as $property) {
					if (preg_match(self::$blacklistedPropertyNames, $property->getName())) {
						continue;
					}
					$dump .= chr(10);
					$dump .= str_repeat(' ', $level) . ($plaintext ? '' : '<span class="debug-property">') . self::ansiEscapeWrap($property->getName(), '36', $ansiColors) . ($plaintext ? '' : '</span>') . ' => ';
					$property->setAccessible(TRUE);
					$value = $property->getValue($object);
					if (is_array($value)) {
						$dump .= self::renderDump($value, $level + 1, $plaintext, $ansiColors);
					} elseif (is_object($value)) {
						$dump .= self::renderObjectDump($value, $level + 1, TRUE, $plaintext, $ansiColors);
					} else {
						$dump .= self::renderDump($value, $level, $plaintext, $ansiColors);
					}
				}
			}
		} elseif (isset(self::$renderedObjects[$objectIdentifier])) {
			if (!$plaintext) {
				$dump = '<a href="#o' . $objectIdentifier . '" onclick="document.location.hash=\'#o' . $objectIdentifier . '\'; return false;" class="debug-seeabove" title="see above">' . $dump . '</a>';
			}
		}
		return $dump;
	}

	/**
	 * Renders some backtrace
	 *
	 * @param array $trace The trace
	 * @param boolean $includeCode Include code snippet
	 * @param boolean $plaintext
	 * @return string Backtrace information
	 */
	static public function getBacktraceCode(array $trace, $includeCode = TRUE, $plaintext = FALSE) {
		$backtraceCode = '';
		if (count($trace)) {
			foreach ($trace as $index => $step) {
				if ($plaintext) {
					$class = isset($step['class']) ? $step['class'] . '::' : '';
				} else {
					$class = isset($step['class']) ? $step['class'] . '<span style="color:white;">::</span>' : '';
				}

				$arguments = '';
				if (isset($step['args']) && is_array($step['args'])) {
					foreach ($step['args'] as $argument) {
						if ($plaintext) {
							$arguments .= (strlen($arguments) === 0) ? '' : ', ';
						} else {
							$arguments .= (strlen($arguments) === 0) ? '' : '<span style="color:white;">,</span> ';
						}
						if (is_object($argument)) {
							if ($plaintext) {
								$arguments .= get_class($argument);
							} else {
								$arguments .= '<span style="color:#FF8700;"><em>' . get_class($argument) . '</em></span>';
							}
						} elseif (is_string($argument)) {
							$preparedArgument = (strlen($argument) < 100) ? $argument : substr($argument, 0, 50) . '…' . substr($argument, -50);
							$preparedArgument = htmlspecialchars($preparedArgument);
							if ($plaintext) {
								$arguments .= '"' . $argument . '"';
							} else {
								$preparedArgument = str_replace("…", '<span style="color:white;">…</span>', $preparedArgument);
								$preparedArgument = str_replace("\n", '<span style="color:white;">⏎</span>', $preparedArgument);
								$arguments .= '"<span style="color:#FF8700;" title="' . htmlspecialchars($argument) . '">' . $preparedArgument . '</span>"';
							}
						} elseif (is_numeric($argument)) {
							if ($plaintext) {
								$arguments .= (string)$argument;
							} else {
								$arguments .= '<span style="color:#FF8700;">' . (string)$argument . '</span>';
							}
						} elseif (is_bool($argument)) {
							if ($plaintext) {
								$arguments .= ($argument === TRUE ? 'TRUE' : 'FALSE');
							} else {
								$arguments .= '<span style="color:#FF8700;">' . ($argument === TRUE ? 'TRUE' : 'FALSE') . '</span>';
							}
						} elseif (is_array($argument)) {
							if ($plaintext) {
								$arguments .= 'array|' . count($argument) . '|';
							} else {
								$arguments .= sprintf(
									'<span style="color:#FF8700;" title="%s"><em>array|%d|</em></span>',
									htmlspecialchars(self::renderArrayDump($argument, 0, TRUE)),
									count($argument)
								);
							}
						} else {
							if ($plaintext) {
								$arguments .= gettype($argument);
							} else {
								$arguments .= '<span style="color:#FF8700;"><em>' . gettype($argument) . '</em></span>';
							}
						}
					}
				}

				if ($plaintext) {
					$backtraceCode .= (count($trace) - $index) . ' ' . $class . $step['function'] . '(' . $arguments . ')';
				} else {
					$backtraceCode .= '<pre style="color:#69A550; background-color: #414141; padding: 4px 2px 4px 2px;">';
					$backtraceCode .= '<span style="color:white;">' . (count($trace) - $index) . '</span> ' . $class . $step['function'] . '<span style="color:white;">(' . $arguments . ')</span>';
					$backtraceCode .= '</pre>';
				}

				if (isset($step['file']) && $includeCode) {
					$backtraceCode .= self::getCodeSnippet($step['file'], $step['line'], $plaintext);
				}
				if ($plaintext) {
					$backtraceCode .= PHP_EOL;
				} else {
					$backtraceCode .= '<br />';
				}
			}
		}

		return $backtraceCode;
	}

	/**
	 * Returns a code snippet from the specified file.
	 *
	 * @param string $filePathAndName Absolute path and filename of the PHP file
	 * @param integer $lineNumber Line number defining the center of the code snippet
	 * @param boolean $plaintext
	 * @return string The code snippet
	 * @todo make plaintext-aware
	 */
	static public function getCodeSnippet($filePathAndName, $lineNumber, $plaintext = FALSE) {
		$pathPosition = strpos($filePathAndName, 'Packages/');
		if ($plaintext) {
			$codeSnippet = PHP_EOL;
		} else {
			$codeSnippet = '<br />';
		}
		if (@file_exists($filePathAndName)) {
			$phpFile = @file($filePathAndName);
			if (is_array($phpFile)) {
				$startLine = ($lineNumber > 2) ? ($lineNumber - 2) : 1;
				$endLine = ($lineNumber < (count($phpFile) - 2)) ? ($lineNumber + 3) : count($phpFile) + 1;
				if ($endLine > $startLine) {
					if ($pathPosition !== FALSE) {
						if ($plaintext) {
							$codeSnippet = PHP_EOL . substr($filePathAndName, $pathPosition) . ':' . PHP_EOL;
						} else {
							$codeSnippet = '<br /><span style="font-size:10px;">' . substr($filePathAndName, $pathPosition) . ':</span><br /><pre>';
						}
					} else {
						if ($plaintext) {
							$codeSnippet = PHP_EOL . $filePathAndName . ':' . PHP_EOL;
						} else {
							$codeSnippet = '<br /><span style="font-size:10px;">' . $filePathAndName . ':</span><br /><pre>';
						}
					}
					for ($line = $startLine; $line < $endLine; $line++) {
						$codeLine = str_replace("\t", ' ', $phpFile[$line-1]);

						if ($line === $lineNumber) {
							if (!$plaintext) {
								$codeSnippet .= '</pre><pre style="background-color: #F1F1F1; color: black;">';
							}
						}
						$codeSnippet .= sprintf('%05d', $line) . ': ';

						if ($plaintext) {
							$codeSnippet .= $codeLine;
						} else {
							$codeSnippet .= htmlspecialchars($codeLine);
						}

						if ($line === $lineNumber && !$plaintext) {
							$codeSnippet .= '</pre><pre>';
						}
					}
					if (!$plaintext) {
						$codeSnippet .= '</pre>';
					}
				}
			}
		}
		return $codeSnippet;
	}

	/**
	 * Wrap a string with the ANSI escape sequence for colorful output
	 *
	 * @param string $string The string to wrap
	 * @param string $ansiColors The ansi color sequence (e.g. "1;37")
	 * @param boolean $enable If FALSE, the raw string will be returned
	 * @return string The wrapped or raw string
	 */
	static protected function ansiEscapeWrap($string, $ansiColors, $enable = TRUE) {
		if ($enable) {
			return "\x1B[" . $ansiColors . 'm' . $string . "\x1B[0m";
		} else {
			return $string;
		}
	}
}

namespace TYPO3\Flow;

/**
 * A var_dump function optimized for Flow's object structures
 *
 * @param mixed $variable The variable to display a dump of
 * @param string $title optional custom title for the debug output
 * @param boolean $return if TRUE, the dump is returned for displaying it embedded in custom HTML. If FALSE (default), the variable dump is directly displayed.
 * @param boolean $plaintext If TRUE, the dump is in plain text, if FALSE the debug output is in HTML format. If not specified, the mode is guessed from FLOW_SAPITYPE
 * @return void|string if $return is TRUE, the variable dump is returned. By default, the dump is directly displayed, and nothing is returned.
 * @api
 */
function var_dump($variable, $title = NULL, $return = FALSE, $plaintext = NULL) {
	if ($plaintext === NULL) {
		$plaintext = (FLOW_SAPITYPE === 'CLI');
		$ansiColors = $plaintext && DIRECTORY_SEPARATOR === '/';
	} else {
		$ansiColors = FALSE;
	}

	if ($title === NULL) {
		$title = 'Flow Variable Dump';
	}
	if ($ansiColors) {
		$title = "\x1B[1m" . $title . "\x1B[0m";
	}
	\TYPO3\Flow\Error\Debugger::clearState();

	if (!$plaintext && \TYPO3\Flow\Error\Debugger::$stylesheetEchoed === FALSE) {
		echo '<link rel="stylesheet" type="text/css" href="/_Resources/Static/Packages/TYPO3.Flow/Error/Debugger.css" />';
		\TYPO3\Flow\Error\Debugger::$stylesheetEchoed = TRUE;
	}

	if ($plaintext) {
		$output = $title . chr(10) . \TYPO3\Flow\Error\Debugger::renderDump($variable, 0, TRUE, $ansiColors) . chr(10) . chr(10);
	} else {
		$output = '
			<div class="Flow-Error-Debugger-VarDump ' . ($return ? 'Flow-Error-Debugger-VarDump-Inline' : 'Flow-Error-Debugger-VarDump-Floating') . '">
				<div class="Flow-Error-Debugger-VarDump-Top">
					' . htmlspecialchars($title) . '
				</div>
				<div class="Flow-Error-Debugger-VarDump-Center">
					<pre dir="ltr">' . \TYPO3\Flow\Error\Debugger::renderDump($variable, 0, FALSE, FALSE) . '</pre>
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
