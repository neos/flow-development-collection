<?php
namespace TYPO3\FLOW3\Error;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A debugging utility class
 *
 */
class Debugger {

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
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
		(TYPO3\\\\FLOW3\\\\Aop.*)
		(TYPO3\\\\FLOW3\\\\Cac.*) |
		(TYPO3\\\\FLOW3\\\\Con.*) |
		(TYPO3\\\\FLOW3\\\\Uti.*) |
		(TYPO3\\\\FLOW3\\\\Mvc\\\\Routing.*) |
		(TYPO3\\\\FLOW3\\\\Log.*) |
		(TYPO3\\\\FLOW3\\\\Obj.*) |
		(TYPO3\\\\FLOW3\\\\Pac.*) |
		(TYPO3\\\\FLOW3\\\\Persistence\\\\(?!Doctrine\\\\Mapping).*) |
		(TYPO3\\\\FLOW3\\\\Pro.*) |
		(TYPO3\\\\FLOW3\\\\Ref.*) |
		(TYPO3\\\\FLOW3\\\\Sec.*) |
		(TYPO3\\\\Fluid\\\\.*) |
		(PHPUnit_Framework_MockObject_InvocationMocker)
		/xs';

	static protected $blacklistedPropertyNames = '/
		(FLOW3_Aop_.*)
		/xs';

	/**
	 * Is set to TRUE once the CSS file is included in the current page to prevent double inclusions of the CSS file.
	 * @var boolean
	 */
	static public $stylesheetEchoed = FALSE;

	/**
	 * Injects the Object Manager
	 *
	 * @param \TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	static public function injectObjectManager(\TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		self::$objectManager = $objectManager;
	}

	/**
	 * Clear the state of the debugger
	 *
	 * @return void
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
			$dump = \TYPO3\FLOW3\Error\Debugger::renderArrayDump($variable, $level + 1, $plaintext, $ansiColors);
		} elseif (is_object($variable)) {
			$dump = \TYPO3\FLOW3\Error\Debugger::renderObjectDump($variable, $level + 1, TRUE, $plaintext, $ansiColors);
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

		if (preg_match(self::$blacklistedClassNames, get_class($object)) !== 0) {
			$renderProperties = FALSE;
		}

		if (self::$objectManager !== NULL) {
			$objectName = self::$objectManager->getObjectNameByClassName(get_class($object));
			if ($objectName !== FALSE) {
				switch(self::$objectManager->getScope($objectName)) {
					case \TYPO3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE :
						$scope = 'prototype';
						break;
					case \TYPO3\FLOW3\Object\Configuration\Configuration::SCOPE_SINGLETON :
						$scope = 'singleton';
						break;
					case \TYPO3\FLOW3\Object\Configuration\Configuration::SCOPE_SESSION :
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
			$identifier = \TYPO3\FLOW3\Reflection\ObjectAccess::getProperty($object, 'FLOW3_Persistence_Identifier', TRUE);
			$persistenceType = 'persistable';
		} else {
			$identifier = 'unknown';
			$persistenceType = 'object';
		}
		if ($plaintext) {
			$dump .= ' ' . self::ansiEscapeWrap($persistenceType, '42;37', $ansiColors);
		} else {
			$dump .= '<span class="debug-ptype" title="' . $identifier . '">' . $persistenceType . '</span>';
		}

		if ($object instanceof \TYPO3\FLOW3\Object\Proxy\ProxyInterface) {
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
					if (preg_match(self::$blacklistedPropertyNames, $property->getName())) continue;
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
								$preparedArgument = str_replace("\n", '⏎', $preparedArgument);
								$arguments .= '(' . $argument . ')' . $preparedArgument;
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
	 * @param string $filePathAndName Absolute path and file name of the PHP file
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

namespace TYPO3\FLOW3;

/**
 * A var_dump function optimized for FLOW3's object structures
 *
 * @param mixed $variable The variable to display a dump of
 * @param string $title optional custom title for the debug output
 * @param boolean $return if TRUE, the dump is returned for displaying it embedded in custom HTML. If FALSE (default), the variable dump is directly displayed.
 * @param boolean $plaintext If TRUE, the dump is in plain text, if FALSE the debug output is in HTML format. If not specified, the mode is guessed from FLOW3_SAPITYPE
 * @return void|string if $return is TRUE, the variable dump is returned. By default, the dump is directly displayed, and nothing is returned.
 * @api
 */
function var_dump($variable, $title = NULL, $return = FALSE, $plaintext = NULL) {
	if ($plaintext === NULL) {
		$plaintext = (FLOW3_SAPITYPE === 'CLI');
		$ansiColors = $plaintext && DIRECTORY_SEPARATOR === '/';
	} else {
		$ansiColors = FALSE;
	}

	if ($title === NULL) {
		$title = 'FLOW3 Variable Dump';
	}
	if ($ansiColors) {
		$title = "\x1B[1m" . $title . "\x1B[0m";
	}
	\TYPO3\FLOW3\Error\Debugger::clearState();

	if (!$plaintext && \TYPO3\FLOW3\Error\Debugger::$stylesheetEchoed === FALSE) {
		echo '<link rel="stylesheet" type="text/css" href="/_Resources/Static/Packages/TYPO3.FLOW3/Error/Debugger.css" />';
		\TYPO3\FLOW3\Error\Debugger::$stylesheetEchoed = TRUE;
	}

	if ($plaintext) {
		$output = $title . chr(10) . \TYPO3\FLOW3\Error\Debugger::renderDump($variable, 0, TRUE, $ansiColors) . chr(10) . chr(10);
	} else {
		$output = '
			<div class="FLOW3-Error-Debugger-VarDump ' . ($return ? 'FLOW3-Error-Debugger-VarDump-Inline' : 'FLOW3-Error-Debugger-VarDump-Floating') . '">
				<div class="FLOW3-Error-Debugger-VarDump-Top">
					' . htmlspecialchars($title) . '
				</div>
				<div class="FLOW3-Error-Debugger-VarDump-Center">
					<pre dir="ltr">' . \TYPO3\FLOW3\Error\Debugger::renderDump($variable, 0, FALSE, FALSE) . '</pre>
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
