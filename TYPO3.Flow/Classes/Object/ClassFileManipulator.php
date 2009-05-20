<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

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
 * @package FLOW3
 * @subpackage Object
 * @version $Id$
 */

/**
 * This class is used to manipulate the PHP source code of certain registered
 * objects. Specifically the "new" operator is replaced by a call to the
 * getObject() method of the object manager.
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 *
 * @todo NOTE: This class does not work at the moment!
 */
class ClassFileManipulator {

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * Constructs the class file manipulator
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager The object manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function __construct(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Mainpulates the specified class file if neccessary and probably overrides
	 * the path and let it point to a manipulated version of the file.
	 *
	 * @param string &$classFilePathAndName Path and name of the class path file
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Fix code for case of existing $targetClassFilePathAndName
	 * @internal
	 */
	public function manipulate(&$classFilePathAndName) {
		$checksum = md5_file($classFilePathAndName);
		$targetClassFilePathAndName = 'FLOW3/Object/' . basename($classFilePathAndName) . $checksum . '.php';
		if (file_exists($targetClassFilePathAndName)) {
			#$classFilePathAndName = $targetClassFilePathAndName;
			#return;
		}

		$sourceCode = file_get_contents($classFilePathAndName);
		$targetCode = '';
		$manipulated = FALSE;
		$tokens = token_get_all($sourceCode);

		$i = 0;
		while ($i < count($tokens)) {
			if (is_array($tokens[$i])) {
				switch ($tokens[$i][0]) {
					case T_IMPLEMENTS :
						#$targetCode .= 'implements \F3\FLOW3\Object\ManagerAwareInterface,';
						break;
					case T_NEW :
						$manipulated = $this->replaceNewOperator($tokens, $i, $targetCode);
						break;
					case T_FILE :
						$targetCode .= "'$classFilePathAndName'";
						break;
					default :
						$targetCode .= $tokens[$i][1];
				}
			} else {
				$targetCode .= $tokens[$i];
			}
			$i++;
		}

		if ($manipulated) {
			file_put_contents($targetClassFilePathAndName, $targetCode);
			$classFilePathAndName = $targetClassFilePathAndName;
		}
	}

	/**
	 * Parses the tokens, starting at the current index and replaces the "new"
	 * operator with a call to the object manager if the class to be instantiated
	 * is registered as an object.
	 *
	 * @param array $tokens Tokens to parse
	 * @param integer &$index Token index to start at
	 * @param string &$targetCode Target source code for replacement
	 * @return boolean Returns TRUE if the new operator really has been replaced, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function replaceNewOperator(array $tokens, &$index, &$targetCode) {
		$index++;
		$newOperatorHasBeenReplaced = FALSE;

		$whitespace = '';
		while ($tokens[$index][0] === T_WHITESPACE) {
			$whitespace .= $tokens[$index][1];
			$index++;
		}

		switch ($tokens[$index][0]) {
			case T_STRING :
				$className = $tokens[$index][1];
				if ($tokens[$index + 1][0] === '(') {
					$index++;
					$constructorArguments = $this->parseConstructorArguments($tokens, $index);
					if ($this->objectManager->isObjectRegistered($className)) {
						$targetCode .= '$GLOBALS[\'TYPO3\']->getObjectManager()->getObject'  . '(\'' . $className . '\', ' . $constructorArguments . ')' . $whitespace;
						$newOperatorHasBeenReplaced = TRUE;
					} else {
						$targetCode .= 'new'  . $whitespace . $className . '(' . $constructorArguments . ')';
					}
				} else {
					if ($this->objectManager->isObjectRegistered($className)) {
						$targetCode .= '$GLOBALS[\'TYPO3\']->getObjectManager()->getObject'  . '(\'' . $className . '\')' . $whitespace;
						$newOperatorHasBeenReplaced = TRUE;
					} else {
						$targetCode .= 'new'  . $whitespace . $className;
					}
				}
				break;
			case T_VARIABLE :
			default :
				$targetCode .= 'new' . $whitespace;
				$targetCode .= $tokens[$index][1];
		}
		return $newOperatorHasBeenReplaced;
	}

	/**
	 * Parses the tokens of the constructor arguments and finds the closing brackets.
	 *
	 * @param array $tokens The tokenized source code
	 * @param integer &$index The current index in the tokens array - the expected starting position is one token after the opening bracket.
	 * @return string returns the content between the parentheses
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function parseConstructorArguments(array $tokens, &$index) {
		$index++;
		$argumentsCode = '';
		while ($tokens[$index][0] != ')') {
			$argumentsCode .= (is_string($tokens[$index])) ? $tokens[$index] : $tokens[$index][1];
			$index++;
		}
		return $argumentsCode;
	}
}
?>