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
 * @package FLOW3
 * @subpackage AOP
 * @version $Id: $
 */

/**
 * An abstract class with additional builder functions for constructor interceptor builders.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:T3_FLOW3_AOP_AbstractConstructorInterceptorBuilder.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
abstract class T3_FLOW3_AOP_AbstractConstructorInterceptorBuilder extends T3_FLOW3_AOP_AbstractMethodInterceptorBuilder {

	/**
	 * Creates code for an array of target methods and their advices.
	 *
	 * Example:
	 *
	 *	$this->targetMethodsAndGroupedAdvices = array(
	 *		'getSomeProperty' => array(
	 *			'T3_FLOW3_AOP_AroundAdvice' => array(
	 *				$this->componentManager->getComponent('T3_FLOW3_AOP_AroundAdvice', 'T3_TestPackage_GetSomeChinesePropertyAspect', 'aroundFourtyTwoToChinese'),
	 *			),
	 *		),
	 *	);
	 *
	 *
	 * @param  array $methodsAndGroupedAdvices: An array of method names and grouped advice objects
	 * @return string PHP code for the content of an array of target method names and advice objects
	 * @author Robert Lemke <robert@typo3.org>
	 * @see    buildProxyClass()
	 */
	protected function buildMethodsAndAdvicesArrayCode(array $methodsAndGroupedAdvices) {
		if (count($methodsAndGroupedAdvices) < 1) return '';

		$methodsAndAdvicesArrayCode = "\n\t\t\$this->targetMethodsAndGroupedAdvices = array(\n";
		foreach ($methodsAndGroupedAdvices as $methodName => $advicesAndDeclaringClass) {
			$methodsAndAdvicesArrayCode .= "\t\t\t'" . $methodName . "' => array(\n";
			foreach ($advicesAndDeclaringClass['groupedAdvices'] as $adviceType => $advices) {
				$methodsAndAdvicesArrayCode .= "\t\t\t\t'" . $adviceType . "' => array(\n";
				foreach ($advices as $advice) {
					$methodsAndAdvicesArrayCode .= "\t\t\t\t\t\$this->componentManager->getComponent('" . get_class($advice) . "', '" . $advice->getAspectComponentName() . "', '" . $advice->getAdviceMethodName() . "', \$this->componentManager),\n";
				}
				$methodsAndAdvicesArrayCode .= "\t\t\t\t),\n";
			}
			$methodsAndAdvicesArrayCode .= "\t\t\t),\n";
		}
		$methodsAndAdvicesArrayCode .= "\t\t);\n";
		return  $methodsAndAdvicesArrayCode;
	}
}
?>