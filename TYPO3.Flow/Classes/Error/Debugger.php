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
 * @version $Id$
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
	 * Hardcoded list of FLOW3 sub packages (first 12 characters) which should not be displayed during debugging
	 * @var array
	 */
	static protected $blacklistedSubPackages = array('F3\FLOW3\Con', 'F3\FLOW3\Err', 'F3\FLOW3\Obj', 'F3\FLOW3\Pac', 'F3\FLOW3\Per', 'F3\FLOW3\Pro', 'F3\FLOW3\Ref', 'F3\FLOW3\Uti');

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
			$dump = sprintf('\'<span class="debug-string">%s</span>\' (length=%s)', htmlspecialchars((strlen($variable) > 1000) ? substr($variable, 0, 1000) . '…' : $variable), strlen($variable));
		} elseif (is_numeric($variable)) {
			$dump = sprintf('%s %s', gettype($variable), $variable);
		} elseif (is_array($variable)) {
			$dump = \F3\FLOW3\Error\Debugger::renderArrayDump($variable, $level + 1);
		} elseif (is_object($variable)) {
			$dump = \F3\FLOW3\Error\Debugger::renderObjectDump($variable, $level + 1);
		} elseif (is_bool($variable)) {
			$dump = $variable ? 'TRUE' : 'FALSE';
		} else {
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
		$dump = 'array' . chr(10);
		foreach ($array as $key => $value) {
			$dump .= str_repeat('   ', $level) . self::renderDump($key, 0) . ' => ';
			if (is_array($value)) {
				$dump .= self::renderDump($value, $level + 1) . chr(10);
			} else {
				$dump .= self::renderDump($value, $level) . chr(10);
			}
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
		$additionalAttributes = '';

		if (self::$objectManager !== NULL) {
			$objectName = self::$objectManager->getObjectNameByClassName(get_class($object));
			if ($objectName !== FALSE) {
				switch(self::$objectManager->getScope($objectName)) {
					case \F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE :
						$scope = 'pr';
						break;
					case \F3\FLOW3\Object\Configuration\Configuration::SCOPE_SINGLETON :
						$scope = 'si';
						break;
					case \F3\FLOW3\Object\Configuration\Configuration::SCOPE_SESSION :
						$scope = 'se';
						break;
				}
				if (self::$renderedObjects->contains($object)) {
					$renderProperties = FALSE;
				} elseif ($renderProperties === TRUE) {
					$scope .= '<a id="' . spl_object_hash($object) . '"></a>';
					self::$renderedObjects->attach($object);
				}
				$dump .= '<span class="debug-scope">' . $scope .'</span>';
			} else {
				$additionalAttributes .= ' debug-unregistered';
			}
		}

		if ($object instanceof \F3\FLOW3\Persistence\Aspect\PersistenceMagicInterface) {
			if (property_exists($object, 'FLOW3_Persistence_Entity_UUID')) {
				$identifier = $object->FLOW3_Persistence_Entity_UUID;
				$persistenceType = 'e';
			} elseif (property_exists($object, 'FLOW3_Persistence_ValueObject_Hash')) {
				$identifier = $object->FLOW3_Persistence_ValueObject_Hash;
				$persistenceType = 'v';
			}
			$dump .= '<span class="debug-ptype" title="' . $identifier . '">' . $persistenceType . '</span>';
		}

		if ($object instanceof \F3\FLOW3\AOP\ProxyInterface) {
			$additionalAttributes .= ' debug-proxy';
			$className = $object->FLOW3_AOP_Proxy_getProxyTargetClassName();
		} else {
			$className = get_class($object);
		}

		$dump .= '<span class="debug-object' . $additionalAttributes . '" title="' . spl_object_hash($object) . '">' . $className . '</span>';

		if ($renderProperties === TRUE) {

			if ($object instanceof \SplObjectStorage) {
				$dump .= chr(10);
				foreach ($object as $value) {
					$dump .= str_repeat('   ', $level);
					if (in_array(substr(get_class($value), 0, 12), self::$blacklistedSubPackages)) {
						$dump .= self::renderObjectDump($value, 0, FALSE) . chr(10);
					} else {
						$dump .= self::renderDump($value, $level + 1) . chr(10);
					}
				}
			} else {
				$classReflection = new \ReflectionClass($className);
				$dump .= chr(10);
				foreach ($classReflection->getProperties() as $property) {
					$dump .= str_repeat('   ', $level) . '<span class="debug-property">' . $property->getName() . '</span> => ';
					$property->setAccessible(TRUE);
					$value = $property->getValue($object);
					if (is_array($value)) {
						$dump .= self::renderDump($value, $level + 1) . chr(10);
					} elseif (is_object($value)) {
						if (in_array(substr(get_class($value), 0, 12), self::$blacklistedSubPackages)) {
							$dump .= self::renderObjectDump($value, 0, FALSE) . chr(10);
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
 * @return void
 * @author Robert Lemke <robert@typo3.org>
 * @api
 */
function var_dump($variable) {
	\F3\FLOW3\Error\Debugger::clearState();
	echo '
		<style>
			.F3-FLOW3-Error-Debugger-VarDump {
				display: block;
				float: left;
				width: 80%;
				position: relative;
				left: 150px;
				z-index: 9100;
				margin: 20px 0 0 0;
			}
			.F3-FLOW3-Error-Debugger-VarDump-Top {
				height: 20px;
				position: relative;
			}
			.F3-FLOW3-Error-Debugger-VarDump-TopLeft {
				background-image: url("/_Resources/Static/Packages/FLOW3/MVC/FloatingWindow/TopLeft.png");
				position: relative;
				float: left;
				right: 0px;
				top: -22px;
				width: 30px;
				height: 42px;
			}
			.F3-FLOW3-Error-Debugger-VarDump-TopRight {
				background-image: url("/_Resources/Static/Packages/FLOW3/MVC/FloatingWindow/TopRight.png");
				position: absolute;
				right: 0px;
				top: -22px;
				width: 40px;
				height: 42px;
			}
			.F3-FLOW3-Error-Debugger-VarDump-TopCenter {
				background-image: url("/_Resources/Static/Packages/FLOW3/MVC/FloatingWindow/Top.png");

				position: relative;
				top: -22px;
				height: 20px;
				padding: 22px 0 0 20px;
				margin: 0 40px 0 30px;
				font-size: 12px;
				font-family: Lucida Grande, sans-serif;
			}
			.F3-FLOW3-Error-Debugger-VarDump-Center {
				background: #b9b9b9;
				position: relative;
				margin: 0 40px 0 30px;
			}

			.F3-FLOW3-Error-Debugger-VarDump-Dump {
				background: #b9b9b9;
				position: relative;
				padding: 20px 10px 20px 20px;
				font-family: Monospaced, Lucida Console, monospace;
				font-size: 10px;
			}

			.F3-FLOW3-Error-Debugger-VarDump-Dump pre {
				font-family: Monospaced, Lucida Console, monospace;
				font-size: 10px;
				line-height: 16px;
			}

			.F3-FLOW3-Error-Debugger-VarDump-Dump, .F3-FLOW3-Error-Debugger-VarDump-Dump p, .F3-FLOW3-Error-Debugger-VarDump-Dump a, .F3-FLOW3-Error-Debugger-VarDump-Dump strong, .F3-FLOW3-Error-Debugger-VarDump-Dump .debug-string{
				font-family: Monospaced, Lucida Console, monospace;
				font-size: 10px;
			}

			.F3-FLOW3-Error-Debugger-VarDump-Dump .debug-string {
				color: #f5f2ba;

			}

			.F3-FLOW3-Error-Debugger-VarDump-Dump .debug-object {
				background-color: #c3d7f1;
				color: #004fb0;
				padding: 1px 2px;
			}

			.F3-FLOW3-Error-Debugger-VarDump-Dump .debug-unregistered {
				background-color: #dce1e8;
			}

			.F3-FLOW3-Error-Debugger-VarDump-Dump .debug-proxy {
				border-left: 5px solid #b0000a;
			}

			.F3-FLOW3-Error-Debugger-VarDump-Dump .debug-scope {
				background-color: #004fb0;
				color: white;
				font-size: 10px;
				font-weight: bold;
				padding: 1px 2px;
			}

			.F3-FLOW3-Error-Debugger-VarDump-Dump .debug-seeabove {
				text-decoration: none;
				font-style: italic;
				font-weight: normal;
			}

			.F3-FLOW3-Error-Debugger-VarDump-Dump .debug-property {
				color: #555555;
				padding: 1px 2px;
			}

			.F3-FLOW3-Error-Debugger-VarDump-Dump .debug-ptype {
				background-color: #87cd3b;
				color: white;
				font-size: 10px;
				font-weight: bold;
				padding: 1px 2px;
			}

			.F3-FLOW3-Error-Debugger-VarDump-Left {
				position: absolute;
				left: -30px;
				width: 30px;
				height: 100%;
				background-image: url("/_Resources/Static/Packages/FLOW3/MVC/FloatingWindow/Left.png");
			}
			.F3-FLOW3-Error-Debugger-VarDump-Right {
				position: absolute;
				right: -40px;
				width: 40px;
				height: 100%;
				background-image: url("/_Resources/Static/Packages/FLOW3/MVC/FloatingWindow/Right.png");
			}
			.F3-FLOW3-Error-Debugger-VarDump-Bottom {
				position: relative;
			}
			.F3-FLOW3-Error-Debugger-VarDump-BottomLeft {
				background-image: url("/_Resources/Static/Packages/FLOW3/MVC/FloatingWindow/BottomLeft.png");
				position: absolute;
				left: 0px;
				width: 30px;
				height: 41px;
			}
			.F3-FLOW3-Error-Debugger-VarDump-BottomRight {
				background-image: url("/_Resources/Static/Packages/FLOW3/MVC/FloatingWindow/BottomRight.png");
				position: absolute;
				right: 0px;
				top: 0px;
				width: 40px;
				height: 41px;
			}
			.F3-FLOW3-Error-Debugger-VarDump-BottomCenter {
				background-image: url("/_Resources/Static/Packages/FLOW3/MVC/FloatingWindow/Bottom.png");
				background-repeat: repeat-x;
				position: relative;
				padding: 22px 0 0 20px;
				margin: 0 40px 0 30px;
				font-size: 14px;
				height: 40px;
			}
		</style>
		<div class="F3-FLOW3-Error-Debugger-VarDump">
			<div class="F3-FLOW3-Error-Debugger-VarDump-Top">
				<div class="F3-FLOW3-Error-Debugger-VarDump-TopLeft">&nbsp;</div>
				<div class="F3-FLOW3-Error-Debugger-VarDump-TopCenter">FLOW3 Variable Dump</div>
				<div class="F3-FLOW3-Error-Debugger-VarDump-TopRight">&nbsp;</div>
			</div>
			<div class="F3-FLOW3-Error-Debugger-VarDump-Center">
				<div class="F3-FLOW3-Error-Debugger-VarDump-Left">&nbsp;</div>
				<div class="F3-FLOW3-Error-Debugger-VarDump-Right">&nbsp;</div>
				<div class="F3-FLOW3-Error-Debugger-VarDump-Dump"><pre dir="ltr">' . \F3\FLOW3\Error\Debugger::renderDump($variable, 0) . '</pre></div>
			</div>
			<div class="F3-FLOW3-Error-Debugger-VarDump-Bottom">
				<div class="F3-FLOW3-Error-Debugger-VarDump-BottomLeft">&nbsp;</div>
				<div class="F3-FLOW3-Error-Debugger-VarDump-BottomCenter">&nbsp;</div>
				<div class="F3-FLOW3-Error-Debugger-VarDump-BottomRight">&nbsp;</div>
			</div>
		</div>
	';
}


?>