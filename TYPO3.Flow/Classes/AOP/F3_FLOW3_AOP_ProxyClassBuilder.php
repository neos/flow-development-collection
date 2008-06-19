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
 * @version $Id$
 */

/**
 * Builds proxy classes for the AOP framework
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3_FLOW3_AOP_ProxyClassBuilder.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_AOP_ProxyClassBuilder {

	const PROXYCLASSSUFFIX = '_AOPProxy';

	/**
	 * Builds a single AOP proxy class for the specified class.
	 *
	 * @param F3_FLOW3_Reflection_Class $targetClass Class to create a proxy class file for
	 * @param array $aspectContainers The array of aspect containers from the AOP Framework
	 * @param string $context The current application context
	 * @return mixed An array containing the proxy class name and its source code if a proxy class has been built, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static public function buildProxyClass(F3_FLOW3_Reflection_Class $targetClass, array $aspectContainers, $context) {
		$introductions = self::getMatchingIntroductions($aspectContainers, $targetClass);
		$introducedInterfaces = self::getInterfaceNamesFromIntroductions($introductions);

		$methodsFromTargetClass = $targetClass->getMethods();
		$methodsFromIntroducedInterfaces = self::getIntroducedMethodsFromIntroductions($introductions, $targetClass);

		$interceptedMethods = array();
		self::addAdvicedMethodsToInterceptedMethods($interceptedMethods, $targetClass, $aspectContainers, ($methodsFromTargetClass + $methodsFromIntroducedInterfaces));
		self::addIntroducedMethodsToInterceptedMethods($interceptedMethods, $targetClass, $methodsFromIntroducedInterfaces);

		if (count($interceptedMethods) < 1 && count($introducedInterfaces) < 1) return FALSE;

		self::addConstructorToInterceptedMethods($interceptedMethods, $targetClass);

		$targetClassName = $targetClass->getName();
		$proxyClassName = self::renderProxyClassName($targetClassName, $context);
		$advicedMethodsInformation = self::getAdvicedMethodsInformation($interceptedMethods);

		$proxyClassTokens = array(
			'CLASS_ANNOTATIONS' => self::buildClassAnnotationsCode($targetClass),
			'PROXY_CLASS_NAME' => $proxyClassName,
			'TARGET_CLASS_NAME' => $targetClassName,
			'INTRODUCED_INTERFACES' => self::buildIntroducedInterfacesCode($introducedInterfaces),
			'METHODS_INTERCEPTOR_CODE' => self::buildMethodsInterceptorCode($interceptedMethods, $targetClass)
		);

		$proxyCode = file_get_contents(FLOW3_PATH_PACKAGES . 'FLOW3/Resources/PHP/AOPProxyClassTemplate.php');
		foreach ($proxyClassTokens as $token => $value) {
			$proxyCode = str_replace('###' . $token . '###', $value, $proxyCode);
		}
		return array('proxyClassName' => $proxyClassName, 'proxyClassCode' => $proxyCode, 'advicedMethodsInformation' => $advicedMethodsInformation);
	}

	/**
	 * Implodes the names of introduced interfaces into a list suitable for the
	 * "implements" clause of the proxy class.
	 *
	 * @param array $introducedInterfaces Names of introduced interfaces
	 * @return string A comma separated list of the above
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function buildIntroducedInterfacesCode(array $introducedInterfaces) {
		$introducedInterfacesCode = '';
		if (count($introducedInterfaces) > 0) {
			$introducedInterfacesCode = implode(', ', $introducedInterfaces) . ', ';
		}
		return $introducedInterfacesCode;
	}

	/**
	 * Traverses all intercepted methods and their advices and builds PHP code to intercept
	 * methods if neccessary. If methods were introduced by an introduction and there's
	 * no advice for them, an empty placeholder method will be generated to meet the
	 * interface contract.
	 *
	 * A constructor will be generated no matter if it existed in the target class or
	 * an advice exists or not.
	 *
	 * @param array $interceptedMethods An array of method names which need to be intercepted
	 * @param F3_FLOW3_Reflection_Class $targetClass The target class the pointcut should match with
	 * @return string Methods interceptor PHP code
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function buildMethodsInterceptorCode(array $interceptedMethods, F3_FLOW3_Reflection_Class $targetClass) {
		$methodsInterceptorCode = '';

		foreach ($interceptedMethods as $methodName => $methodMetaInformation) {
			$hasAdvices = (count($methodMetaInformation['groupedAdvices']) > 0);
			$isConstructor = $methodMetaInformation['isConstructor'];
			$builderClassName = 'F3_FLOW3_AOP_' . ($hasAdvices ? 'Adviced' : 'Empty') . ($isConstructor ? 'Constructor' : 'Method') . 'InterceptorBuilder';

			$methodsInterceptorCode .= call_user_func_array(array($builderClassName, 'build'), array($methodName, $interceptedMethods, $targetClass));
		}
		return $methodsInterceptorCode;
	}

	/**
	 * Traverses all aspect containers, their aspects and their advisors and adds the
	 * methods and their advices to the (usually empty) array of intercepted methods.
	 *
	 * @param array &$interceptedMethods An array (empty or not) which contains the names of the intercepted methods and additional information
	 * @param F3_FLOW3_Reflection_Class $targetClass Class the pointcut should match with
	 * @param array $aspectContainers All aspects to take into consideration
	 * @param array $methods: An array of methods which are matched against the pointcut
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function addAdvicedMethodsToInterceptedMethods(array &$interceptedMethods, F3_FLOW3_Reflection_Class $targetClass, $aspectContainers, $methods) {
		$pointcutQueryIdentifier = 0;
		$constructor = $targetClass->getConstructor();
		$constructorName = ($constructor instanceof F3_FLOW3_Reflection_Method) ? $constructor->getName() : '__construct';

		foreach ($aspectContainers as $aspectContainer) {
			foreach ($aspectContainer->getAdvisors() as $advisor) {
				$pointcut = $advisor->getPointcut();
				foreach ($methods as $method) {
					if ($pointcut->matches($targetClass, $method, $pointcutQueryIdentifier)) {
						$advice = $advisor->getAdvice();
						$methodName = $method->getName();
						$interceptedMethods[$methodName]['groupedAdvices'][get_class($advice)][] = $advice;
						$interceptedMethods[$methodName]['declaringClass'] = $method->getDeclaringClass();
						$interceptedMethods[$methodName]['isConstructor'] = ($methodName === $constructorName);
					}
					$pointcutQueryIdentifier ++;
				}
			}
		}
	}

	/**
	 * Traverses all methods which were introduced by interfaces and adds them to the
	 * intercepted methods array if they didn't exist already.
	 *
	 * @param array &$interceptedMethods An array (empty or not) which contains the names of the intercepted methods and additional information
	 * @param F3_FLOW3_Reflection_Class $targetClass Class the pointcut should match with
	 * @param array $methodsFromIntroducedInterfaces An array of F3_FLOW3_Reflection_Method from introduced interfaces
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function addIntroducedMethodsToInterceptedMethods(array &$interceptedMethods, F3_FLOW3_Reflection_Class $targetClass, array $methodsFromIntroducedInterfaces) {
		$constructor = $targetClass->getConstructor();
		$constructorName = ($constructor instanceof F3_FLOW3_Reflection_Method) ? $constructor->getName() : '__construct';

		foreach ($methodsFromIntroducedInterfaces as $method) {
			$methodName = $method->getName();
			if (!isset($interceptedMethods[$methodName]) && $method->getDeclaringClass()->isInterface()) {
				$interceptedMethods[$methodName]['groupedAdvices'] = array();
				$interceptedMethods[$methodName]['declaringClass'] = $method->getDeclaringClass();
				$interceptedMethods[$methodName]['isConstructor'] = ($methodName === $constructorName);
			}
		}
	}

	/**
	 * Asserts that a constructor exists, even if there is none in the original class
	 * and even though no advice exists for it. If a constructor had to be added,
	 * it will be added to the intercepted methods array.
	 *
	 * @param array &$interceptedMethods An array (empty or not) which contains the names of the intercepted methods and additional information
	 * @param F3_FLOW3_Reflection_Class $targetClass Class the pointcut should match with
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function addConstructorToInterceptedMethods(array &$interceptedMethods, F3_FLOW3_Reflection_Class $targetClass) {
		$constructor = $targetClass->getConstructor();
		$constructorName = ($constructor instanceof F3_FLOW3_Reflection_Method) ? $constructor->getName() : '__construct';

		$declaringClass = ($constructor instanceof F3_FLOW3_Reflection_Method) ? $constructor->getDeclaringClass() : NULL;
		if (!isset($interceptedMethods[$constructorName])) {
			$interceptedMethods[$constructorName]['groupedAdvices'] = array();
			$interceptedMethods[$constructorName]['declaringClass'] = $declaringClass;
			$interceptedMethods[$constructorName]['isConstructor'] = TRUE;
		}
	}

	/**
	 * Traverses all aspect containers and returns an array of introductions
	 * which match the target class.
	 *
	 * @param array $aspectContainers All aspects to take into consideration
	 * @param  F3_FLOW3_Reflection_Class $targetClass Class the pointcut should match with
	 * @return array array of interface names
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function getMatchingIntroductions(array $aspectContainers, F3_FLOW3_Reflection_Class $targetClass) {
		$introductions = array();
		$dummyMethod = new F3_FLOW3_Reflection_Method(__CLASS__, 'dummyMethod');

		foreach ($aspectContainers as $aspectContainer) {
			foreach ($aspectContainer->getIntroductions() as $introduction) {
				$pointcut = $introduction->getPointcut();
				if ($pointcut->matches($targetClass, $dummyMethod, uniqid())) {
					$introductions[] = $introduction;
				}
			}
		}
		return $introductions;
	}

	/**
	 * This method is used by getMatchingIntroductions()
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see getMatchingIntroductions()
	 */
	protected function dummyMethod() {
	}

	/**
	 * Returns an array of interface names introduced by the given introductions
	 *
	 * @param array $introductions An array of introductions
	 * @return array Array of interface reflections
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function getInterfaceNamesFromIntroductions(array $introductions) {
		$interfaceNames = array();
		foreach ($introductions as $introduction) {
			$interfaceNames[] = $introduction->getInterface()->getName();
		}
		return $interfaceNames;
	}

	/**
	 * Returns all methods declared by the introduced interfaces
	 *
	 * @param array $introductions An array of F3_FLOW3_AOP_Introduction
	 * @return array An array of F3_FLOW3_Reflection_Method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function getIntroducedMethodsFromIntroductions(array $introductions) {
		$methods = array();
		$methodsAndIntroductions = array();
		foreach ($introductions as $introduction) {
			$interface = $introduction->getInterface();
			foreach ($interface->getMethods() as $newMethod) {
				$newMethodName = $newMethod->getName();
				if (isset($methods[$newMethodName])) throw new F3_FLOW3_AOP_Exception('Method name conflict! Method "' . $newMethodName . '" introduced by "' . $interface->getName() . '" declared in aspect "' . $introduction->getDeclaringAspectClassName() . '" has already been introduced by "' . $methodsAndIntroductions[$newMethodName]->getInterfaceName() . '" declared in aspect "' . $methodsAndIntroductions[$newMethodName]->getDeclaringAspectClassName() . '".', 1173020942);
				$methods[$newMethodName] = $newMethod;
				$methodsAndIntroductions[$newMethodName] = $introduction;
			}
		}
		return $methods;
	}

	/**
	 * Creates an array of method names and names of advices which have been applied
	 * to them. This information is only used for debugging and AOP browsers,
	 * not for the building process itself.
	 *
	 * @param array $interceptedMethods An array of intercepted methods and their grouped Advices etc.
	 * @return array Method names and an array of advice names in the form of "AspectClassName::adviceName"
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function getAdvicedMethodsInformation(array $interceptedMethods) {
		$advicedMethodsInformation = array();
		foreach ($interceptedMethods as $methodName => $interceptionInformation) {
			foreach ($interceptionInformation['groupedAdvices'] as $adviceType => $advices) {
				foreach ($advices as $advice) {
					$advicedMethodsInformation[$methodName][$adviceType][] = array (
						'aspectComponentName' => $advice->getAspectComponentName(),
						'adviceMethodName' => $advice->getAdviceMethodName()
					);
				}
			}
		}
		return $advicedMethodsInformation;
	}

	/**
	 * Creates inline comments with annotations which were defined in the target class
	 *
	 * @param F3_FLOW3_Reflection_Class $class
	 * @return string PHP code snippet containing the annotations
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function buildClassAnnotationsCode(F3_FLOW3_Reflection_Class $class) {
		$annotationsCode = '';
		foreach ($class->getTagsValues() as $tag => $values) {
			$annotationsCode .= ' * @' . $tag . ' ' . implode(' ', $values) . chr(10);
		}
		return $annotationsCode;
	}

	/**
	 * Renders a valid, unique class name for the proxy class
	 *
	 * @param string $targetClassName: Name of the proxied class
	 * @param string $context: The current application context
	 * @return string Name for the proxy class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function renderProxyClassName($targetClassName, $context) {
		$proxyClassName = $targetClassName . self::PROXYCLASSSUFFIX . '_' . $context;
		if (class_exists($proxyClassName, FALSE)) {
			$proxyClassVersion = 2;
			while (class_exists($targetClassName . self::PROXYCLASSSUFFIX . '_' . $context . '_v' . $proxyClassVersion , FALSE)) {
				$proxyClassVersion++;
			}
			$proxyClassName = $targetClassName . self::PROXYCLASSSUFFIX . '_' . $context . '_v' . $proxyClassVersion;
		}
		return $proxyClassName;
	}
}
?>