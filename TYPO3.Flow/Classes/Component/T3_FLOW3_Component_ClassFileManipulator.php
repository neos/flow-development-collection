<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * This class is used to manipulate the PHP source code of certain registered
 * components. Specifically the "new" operator is replaced by a call to the
 * getComponent() method of the component manager.
 *
 * @package		FLOW3
 * @subpackage	Component
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 *
 * @todo NOTE: This class does not work at the moment!
 */
class T3_FLOW3_Component_ClassFileManipulator {

	/**
	 * @var T3_FLOW3_Component_ManagerInterface
	 */
	protected $componentManager;

	/**
	 * Constructs the class file manipulator
	 *
	 * @param  T3_FLOW3_Component_ManagerInterface		$componentManager: The component manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(T3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
	}

	/**
	 * Mainpulates the specified class file if neccessary and probably overrides
	 * the path and let it point to a manipulated version of the file.
	 *
	 * @param  string					&$classFilePathAndName: Path and name of the class path file
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function manipulate(&$classFilePathAndName) {
		$checksum = md5_file($classFilePathAndName);
		$targetClassFilePathAndName = FLOW3_PATH_PRIVATEFILECACHE . 'FLOW3/Component/' . basename($classFilePathAndName) . $checksum . '.php';
		if (file_exists($targetClassFilePathAndName)) {
#			$classFilePathAndName = $targetClassFilePathAndName;
#			return;
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
						#$targetCode .= 'implements T3_FLOW3_Component_ManagerAwareInterface,';
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
	 * operator with a call to the component manager if the class to be instantiated
	 * is registered as a component.
	 *
	 * @param
	 * @param
	 * @param
	 * @return boolean					Returns TRUE if the new operator really has been replaced, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function replaceNewOperator(Array $tokens, &$index, &$targetCode) {
		$index++;
		$newOperatorHasBeenReplaced = FALSE;

		$whitespace = '';
		while ($tokens[$index][0] == T_WHITESPACE) {
			$whitespace .= $tokens[$index][1];
			$index++;
		}

		switch ($tokens[$index][0]) {
			case T_STRING :
				$className = $tokens[$index][1];
				if ($tokens[$index + 1][0] == '(') {
					$index++;
					$constructorArguments = $this->parseConstructorArguments($tokens, $index);
					if ($this->componentManager->isComponentRegistered($className)) {
						$targetCode .= '$GLOBALS[\'TYPO3\']->getComponentManager()->getComponent'  . '(\'' . $className . '\', ' . $constructorArguments . ')' . $whitespace;
						$newOperatorHasBeenReplaced = TRUE;
					} else {
						$targetCode .= 'new'  . $whitespace . $className . '(' . $constructorArguments . ')';
					}
				} else {
					if ($this->componentManager->isComponentRegistered($className)) {
						$targetCode .= '$GLOBALS[\'TYPO3\']->getComponentManager()->getComponent'  . '(\'' . $className . '\')' . $whitespace;
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
	 * @param  array					$tokens: The tokenized source code
	 * @param  integer					&$index: The current index in the tokens array - the expected starting position is one token after the opening bracket.
	 * @return string					returns the content between the parentheses
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseConstructorArguments(Array $tokens, &$index) {
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