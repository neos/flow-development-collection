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
 * Builds proxy classes for the AOP framework
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:T3_FLOW3_AOP_ProxyClassBuilder.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_AOP_ProxyClassBuilder {

	const PROXYCLASSSUFFIX = '_AOPProxy';

	/**
	 * @var T3_FLOW3_Component_ManagerInterface An instance of the component manager
	 */
	protected $componentManager;

	/**
	 * Constructor
	 *
	 * @param  T3_FLOW3_Component_ManagerInterface $componentManager: An instance of the component manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(T3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
	}

	/**
	 * Builds a single AOP proxy class for the specified class using its component
	 * configuration. The component configuration will be modified and returned.
	 *
	 * @param  T3_FLOW3_Reflection_Class $targetClass: Class to create a proxy class file for
	 * @param  T3_FLOW3_Component_Configuration $componentConfiguration: The component configuration for that class
	 * @param  array $aspectContainers: The array of aspect containers from the AOP Framework
	 * @return T3_FLOW3_Component_Configuration The modified component configuration
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildProxyClass(T3_FLOW3_Reflection_Class $targetClass, T3_FLOW3_Component_Configuration $componentConfiguration, array $aspectContainers) {
		$introductions = $this->getMatchingIntroductions($aspectContainers, $targetClass);
		$introducedInterfaces = $this->getInterfaceNamesFromIntroductions($introductions);

		$methodsFromTargetClass = $targetClass->getMethods();
		$methodsFromIntroducedInterfaces = $this->getIntroducedMethodsFromIntroductions($introductions, $targetClass);

		$interceptedMethods = array();
		$this->addAdvicedMethodsToInterceptedMethods($interceptedMethods, $targetClass, $aspectContainers, ($methodsFromTargetClass + $methodsFromIntroducedInterfaces));
		$this->addIntroducedMethodsToInterceptedMethods($interceptedMethods, $targetClass, $methodsFromIntroducedInterfaces);

		if (count($interceptedMethods) < 1 && count($introducedInterfaces) < 1) return $componentConfiguration;

		$this->addConstructorToInterceptedMethods($interceptedMethods, $targetClass);

		$targetClassName = $targetClass->getName();
		$proxyClassTokens = array(
			'CLASS_ANNOTATIONS' => $this->buildClassAnnotationsCode($targetClass),
			'TARGET_CLASS' => $targetClassName,
			'PROXY_CLASS_SUFFIX' => self::PROXYCLASSSUFFIX,
			'INTRODUCED_INTERFACES' => $this->buildIntroducedInterfacesCode($introducedInterfaces),
			'METHODS_INTERCEPTOR_CODE' => $this->buildMethodsInterceptorCode($interceptedMethods, $targetClass)
		);

		$targetClassName = $proxyClassTokens['TARGET_CLASS'];
		$proxyClassName = $targetClassName . self::PROXYCLASSSUFFIX;
		if (!class_exists($proxyClassName)) {
			$proxyCode = file_get_contents(TYPO3_PATH_PACKAGES . 'FLOW3/Resources/PHP/AOPProxyClassTemplate.php');
			foreach ($proxyClassTokens as $token => $value) {
				$proxyCode = str_replace('###' . $token . '###', $value, $proxyCode);
			}
			eval($proxyCode);
		}

		$componentConfiguration->setClassName($proxyClassName);
		$constructorArguments = $componentConfiguration->getConstructorArguments();
		if (count($constructorArguments) > 0) {
			$componentConfiguration->setConstructorArgument(new T3_FLOW3_Component_ConfigurationArgument(count($constructorArguments) + 1, 'T3_FLOW3_Component_ManagerInterface', T3_FLOW3_Component_ConfigurationArgument::ARGUMENT_TYPES_REFERENCE));
		}

		return $componentConfiguration;
	}

	/**
	 * Implodes the names of introduced interfaces into a list suitable for the
	 * "implements" clause of the proxy class.
	 *
	 * @param  array $introducedInterfaces: Names of introduced interfaces
	 * @return string A comma separated list of the above
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildIntroducedInterfacesCode($introducedInterfaces) {
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
	 * @param  array $interceptedMethods: An array of method names which need to be intercepted
	 * @param  T3_FLOW3_Reflection_Class $targetClass: The target class the pointcut should match with
	 * @return string Methods interceptor PHP code
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildMethodsInterceptorCode($interceptedMethods, T3_FLOW3_Reflection_Class $targetClass) {
		$methodsInterceptorCode = '';

		$advicedMethodInterceptorBuilder = new T3_FLOW3_AOP_AdvicedMethodInterceptorBuilder();
		$emptyMethodInterceptorBuilder = new T3_FLOW3_AOP_EmptyMethodInterceptorBuilder();
		$advicedConstructorInterceptorBuilder = new T3_FLOW3_AOP_AdvicedConstructorInterceptorBuilder();
		$emptyConstructorInterceptorBuilder = new T3_FLOW3_AOP_EmptyConstructorInterceptorBuilder();

		foreach ($interceptedMethods as $methodName => $methodMetaInformation) {
			$hasAdvices = (count($methodMetaInformation['groupedAdvices']) > 0);
			$isConstructor = $methodMetaInformation['isConstructor'];
			$builderName = ($hasAdvices ? 'adviced' : 'empty') . ($isConstructor ? 'Constructor' : 'Method') . 'InterceptorBuilder';

			$methodsInterceptorCode .= $$builderName->build($methodName, $interceptedMethods, $targetClass);
		}
		return $methodsInterceptorCode;
	}

	/**
	 * Traverses all aspect containers, their aspects and their advisors and adds the
	 * methods and their advices to the (usually empty) array of intercepted methods.
	 *
	 * @param  array &$interceptedMethods: An array (empty or not) which contains the names of the intercepted methods and additional information
	 * @param  ReflectionClass $targetClass: Class the pointcut should match with
	 * @param  array $aspectContainers: All aspects to take into consideration
	 * @param  array $methods: An array of methods which are matched against the pointcut
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function addAdvicedMethodsToInterceptedMethods(array &$interceptedMethods, ReflectionClass $targetClass, $aspectContainers, $methods) {
		$pointcutQueryIdentifier = 0;
		$constructor = $targetClass->getConstructor();
		$constructorName = ($constructor instanceof ReflectionMethod) ? $constructor->getName() : '__construct';

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
	 * @param  array &$interceptedMethods: An array (empty or not) which contains the names of the intercepted methods and additional information
	 * @param  ReflectionClass $targetClass: Class the pointcut should match with
	 * @param  array $methodsFromIntroducedInterfaces: An array of ReflectionMethod from introduced interfaces
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function addIntroducedMethodsToInterceptedMethods(array &$interceptedMethods, ReflectionClass $targetClass, array $methodsFromIntroducedInterfaces) {
		$constructor = $targetClass->getConstructor();
		$constructorName = ($constructor instanceof ReflectionMethod) ? $constructor->getName() : '__construct';

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
	 * @param  array &$interceptedMethods: An array (empty or not) which contains the names of the intercepted methods and additional information
	 * @param  ReflectionClass $targetClass: Class the pointcut should match with
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function addConstructorToInterceptedMethods(array &$interceptedMethods, ReflectionClass $targetClass) {
		$constructor = $targetClass->getConstructor();
		$constructorName = ($constructor instanceof ReflectionMethod) ? $constructor->getName() : '__construct';

		$declaringClass = ($constructor instanceof ReflectionMethod) ? $constructor->getDeclaringClass() : NULL;
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
	 * @param  array $aspectContainers: All aspects to take into consideration
	 * @param  T3_FLOW3_Reflection_Class $targetClass: Class the pointcut should match with
	 * @return array array of interface names
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getMatchingIntroductions(array $aspectContainers, T3_FLOW3_Reflection_Class $targetClass) {
		$introductions = array();
		$dummyMethod = new ReflectionMethod(__CLASS__, 'dummyMethod');

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
	 * @see    getMatchingIntroductions()
	 */
	protected function dummyMethod() {
	}

	/**
	 * Returns an array of interface names introduced by the given introductions
	 *
	 * @param  array $introductions: An array of introductions
	 * @return array array of interface names
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getInterfaceNamesFromIntroductions(array $introductions) {
		$interfaceNames = array();
		foreach ($introductions as $introduction) {
			$interfaceNames[] = $introduction->getInterfaceName();
		}
		return $interfaceNames;
	}

	/**
	 * Returns all methods declared by the introduced interfaces
	 *
	 * @param  array $introductions: An array of T3_FLOW3_AOP_IntroductionInterface
	 * @return array An array of ReflectionMethods
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getIntroducedMethodsFromIntroductions(array $introductions) {
		$methods = array();
		$methodsAndIntroductions = array();
		foreach ($introductions as $introduction) {
			$interface = new ReflectionClass($introduction->getInterfaceName());
			foreach ($interface->getMethods() as $newMethod) {
				$newMethodName = $newMethod->getName();
				if (isset($methods[$newMethodName])) throw new RuntimeException('Method name conflict! Method "' . $newMethodName . '" introduced by "' . $interface->getName() . '" declared in aspect "' . $introduction->getDeclaringAspectClassName() . '" has already been introduced by "' . $methodsAndIntroductions[$newMethodName]->getInterfaceName() . '" declared in aspect "' . $methodsAndIntroductions[$newMethodName]->getDeclaringAspectClassName() . '".', 1173020942);
				$methods[$newMethodName] = $newMethod;
				$methodsAndIntroductions[$newMethodName] = $introduction;
			}
		}
		return $methods;
	}

	/**
	 * Creates inline comments with annotations which were defined in the target class
	 *
	 * @param  T3_FLOW3_Reflection_Class $class
	 * @return string PHP code snippet containing the annotations
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildClassAnnotationsCode(T3_FLOW3_Reflection_Class $class) {
		$annotationsCode = '';
		foreach ($class->getTagsValues() as $tag => $values) {
			$annotationsCode .= ' * @' . $tag . ' ' . implode(' ', $values) . chr(10);
		}
		return $annotationsCode;
	}
}
?>