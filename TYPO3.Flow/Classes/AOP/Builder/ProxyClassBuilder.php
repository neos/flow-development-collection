<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP\Builder;

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
 * @subpackage AOP
 * @version $Id$
 */

/**
 * Builds proxy classes for the AOP framework
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ProxyClassBuilder {

	const PROXYCLASSSUFFIX = '_AOPProxy';

	/**
	 * @var F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * PHP code template for building proxy classes
	 *
	 * @var string
	 */
	protected $proxyClassTemplate;

	/**
	 * @var array
	 */
	protected $methodInterceptorBuilders = array();

	/**
	 * Injects the reflection service
	 *
	 * @param F3\FLOW3\Reflection\Service $reflectionService The reflection service
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the Empty Constructor Interceptor Builder
	 *
	 * @param EmptyConstructorInterceptorBuilder $builder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectEmptyConstructorInterceptorBuilder(\F3\FLOW3\AOP\Builder\EmptyConstructorInterceptorBuilder $builder) {
		$this->methodInterceptorBuilders['EmptyConstructor'] = $builder;
	}

	/**
	 * Injects the Adviced Constructor Interceptor Builder
	 *
	 * @param AdvicedConstructorInterceptorBuilder $builder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectAdvicedConstructorInterceptorBuilder(\F3\FLOW3\AOP\Builder\AdvicedConstructorInterceptorBuilder $builder) {
		$this->methodInterceptorBuilders['AdvicedConstructor'] = $builder;
	}

	/**
	 * Injects the Empty Method Interceptor Builder
	 *
	 * @param EmptyMethodInterceptorBuilder $builder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectEmptyMethodInterceptorBuilder(\F3\FLOW3\AOP\Builder\EmptyMethodInterceptorBuilder $builder) {
		$this->methodInterceptorBuilders['EmptyMethod'] = $builder;
	}

	/**
	 * Injects the Adviced Method Interceptor Builder
	 *
	 * @param AdvicedMethodInterceptorBuilder $builder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectAdvicedMethodInterceptorBuilder(\F3\FLOW3\AOP\Builder\AdvicedMethodInterceptorBuilder $builder) {
		$this->methodInterceptorBuilders['AdvicedMethod'] = $builder;
	}

	/**
	 * Sets the proxy class template
	 *
	 * @param string $proxyClassTemplate Template to use for building proxy classes
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function setProxyClassTemplate($proxyClassTemplate) {
		$this->proxyClassTemplate = $proxyClassTemplate;
	}

	/**
	 * Initializes this proxy class builder
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function initializeObject() {
		$this->proxyClassTemplate = file_get_contents(FLOW3_PATH_FLOW3 . 'Resources/Private/AOP/AOPProxyClassTemplate.php');
	}

	/**
	 * Builds a single AOP proxy class for the specified class.
	 *
	 * @param string $targetClassName Name of the class to create a proxy class file for
	 * @param array $aspectContainers The array of aspect containers from the AOP Framework
	 * @param string $context The current application context
	 * @return mixed An array containing the proxy class name and its source code if a proxy class has been built, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @internal
	 */
	public function buildProxyClass($targetClassName, array $aspectContainers, $context) {
		if ($this->reflectionService->isClassImplementationOf($targetClassName, 'F3\FLOW3\AOP\ProxyInterface')) throw new \F3\FLOW3\AOP\Exception\InvalidTargetClass('Cannot proxy class "' . $targetClassName . '" because it is already an AOP proxy class.', 1238858632);

		$introductions = $this->getMatchingIntroductions($aspectContainers, $targetClassName);
		$introducedInterfaces = $this->getInterfaceNamesFromIntroductions($introductions);

		$methodsFromTargetClass = $this->getMethodsFromTargetClass($targetClassName);
		$methodsFromIntroducedInterfaces = $this->getIntroducedMethodsFromIntroductions($introductions, $targetClassName);

		$interceptedMethods = array();
		$this->addAdvicedMethodsToInterceptedMethods($interceptedMethods, array_merge($methodsFromTargetClass, $methodsFromIntroducedInterfaces), $targetClassName, $aspectContainers);
		$this->addIntroducedMethodsToInterceptedMethods($interceptedMethods, $methodsFromIntroducedInterfaces);
		if (count($interceptedMethods) < 1 && count($introducedInterfaces) < 1) return FALSE;

		$this->addConstructorToInterceptedMethods($interceptedMethods, $targetClassName);

		$proxyClassName = $this->renderProxyClassName($targetClassName, $context);
		$proxyNamespace = $this->getProxyNamespace($targetClassName);
		$advicedMethodsInformation = $this->getAdvicedMethodsInformation($interceptedMethods);

		$proxyClassTokens = array(
			'CLASS_ANNOTATIONS' => $this->buildClassAnnotationsCode($targetClassName),
			'PROXY_NAMESPACE' => $proxyNamespace,
			'PROXY_CLASS_NAME' => $proxyClassName,
			'TARGET_CLASS_NAME' => $targetClassName,
			'INTRODUCED_INTERFACES' => $this->buildIntroducedInterfacesCode($introducedInterfaces),
			'METHODS_AND_ADVICES_ARRAY_CODE' => $this->buildMethodsAndAdvicesArrayCode($interceptedMethods),
			'METHODS_INTERCEPTOR_CODE' => $this->buildMethodsInterceptorCode($interceptedMethods, $targetClassName)
		);

		$proxyCode = $this->proxyClassTemplate;
		foreach ($proxyClassTokens as $token => $value) {
			$proxyCode = str_replace('###' . $token . '###', $value, $proxyCode);
		}
		return array('proxyClassName' => $proxyNamespace . '\\' . $proxyClassName, 'proxyClassCode' => $proxyCode, 'advicedMethodsInformation' => $advicedMethodsInformation);
	}

	/**
	 * Returns the methods of the target class. If no constructor exists in the target class,
	 * it will nonetheless be added to the list of methods.
	 *
	 * @param string $targetClassName Name of the target class
	 * @return array Method information with declaring class and method name pairs
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function getMethodsFromTargetClass($targetClassName) {
		$methods = array();
		$existingMethodNames = $this->reflectionService->getClassMethodNames($targetClassName);

		if (array_search('__construct', $existingMethodNames) === FALSE) $methods[] = array(NULL, '__construct');
		foreach ($existingMethodNames as $methodName) {
			$methods[] = array($targetClassName, $methodName);
		}

		return $methods;
	}

	/**
	 * Implodes the names of introduced interfaces into a list suitable for the
	 * "implements" clause of the proxy class.
	 *
	 * @param array $introducedInterfaces Names of introduced interfaces
	 * @return string A comma separated list of the above
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function buildIntroducedInterfacesCode(array $introducedInterfaces) {
		$introducedInterfacesCode = '';
		if (count($introducedInterfaces) > 0) {
			$introducedInterfacesCode = implode(', ', $introducedInterfaces) . ', ';
		}
		return $introducedInterfacesCode;
	}

	/**
	 * Creates code for an array of target methods and their advices.
	 *
	 * Example:
	 *
	 *	$this->targetMethodsAndGroupedAdvices = array(
	 *		'getSomeProperty' => array(
	 *			'F3\FLOW3\AOP\Advice\AroundAdvice' => array(
	 *				$this->objectFactory->create('F3\FLOW3\AOP\Advice\AroundAdvice', 'F3\Foo\SomeAspect', 'aroundAdvice'),
	 *			),
	 *		),
	 *	);
	 *
	 *
	 * @param array $methodsAndGroupedAdvices An array of method names and grouped advice objects
	 * @return string PHP code for the content of an array of target method names and advice objects
	 * @author Robert Lemke <robert@typo3.org>
	 * @see buildProxyClass()
	 * @internal
	 */
	protected function buildMethodsAndAdvicesArrayCode(array $methodsAndGroupedAdvices) {
		if (count($methodsAndGroupedAdvices) < 1) return '';

		$methodsAndAdvicesArrayCode = "\n\t\t\$this->targetMethodsAndGroupedAdvices = array(\n";
		foreach ($methodsAndGroupedAdvices as $methodName => $advicesAndDeclaringClass) {
			$methodsAndAdvicesArrayCode .= "\t\t\t'" . $methodName . "' => array(\n";
			foreach ($advicesAndDeclaringClass['groupedAdvices'] as $adviceType => $advices) {
				$methodsAndAdvicesArrayCode .= "\t\t\t\t'" . $adviceType . "' => array(\n";
				foreach ($advices as $advice) {
					$methodsAndAdvicesArrayCode .= "\t\t\t\t\tnew \\" . get_class($advice) . "('" . $advice->getAspectObjectName() . "', '" . $advice->getAdviceMethodName() . "', \$this->objectManager),\n";
				}
				$methodsAndAdvicesArrayCode .= "\t\t\t\t),\n";
			}
			$methodsAndAdvicesArrayCode .= "\t\t\t),\n";
		}
		$methodsAndAdvicesArrayCode .= "\t\t);\n";
		return  $methodsAndAdvicesArrayCode;
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
	 * @param $targetClassName The target class the pointcut should match with
	 * @return string Methods interceptor PHP code
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function buildMethodsInterceptorCode(array $interceptedMethods, $targetClassName) {
		$methodsInterceptorCode = '';
		foreach ($interceptedMethods as $methodName => $methodMetaInformation) {
			$hasAdvices = (count($methodMetaInformation['groupedAdvices']) > 0);
			$builderType = ($hasAdvices ? 'Adviced' : 'Empty') . ($methodName === '__construct' ? 'Constructor' : 'Method');
			$methodsInterceptorCode .= $this->methodInterceptorBuilders[$builderType]->build($methodName, $interceptedMethods, $targetClassName);
		}
		return $methodsInterceptorCode;
	}

	/**
	 * Traverses all aspect containers, their aspects and their advisors and adds the
	 * methods and their advices to the (usually empty) array of intercepted methods.
	 *
	 * @param array &$interceptedMethods An array (empty or not) which contains the names of the intercepted methods and additional information
	 * @param array $methods An array of class and method names which are matched against the pointcut (class name = name of the class or interface the method was declared)
	 * @param string $targetClassName Name of the class the pointcut should match with
	 * @param array $aspectContainers All aspects to take into consideration
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function addAdvicedMethodsToInterceptedMethods(array &$interceptedMethods, array $methods, $targetClassName, array $aspectContainers) {
		$pointcutQueryIdentifier = 0;

		foreach ($aspectContainers as $aspectContainer) {
			foreach ($aspectContainer->getAdvisors() as $advisor) {
				$pointcut = $advisor->getPointcut();
				foreach ($methods as $method) {
					list($methodDeclaringClassName, $methodName) = $method;
					if ($pointcut->matches($targetClassName, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)) {
						$advice = $advisor->getAdvice();
						$interceptedMethods[$methodName]['groupedAdvices'][get_class($advice)][] = $advice;
						$interceptedMethods[$methodName]['declaringClassName'] = $methodDeclaringClassName;
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
	 * @param array $methodsFromIntroducedInterfaces An array of class and method names from introduced interfaces
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function addIntroducedMethodsToInterceptedMethods(array &$interceptedMethods, array $methodsFromIntroducedInterfaces) {
		foreach ($methodsFromIntroducedInterfaces as $interfaceAndMethodName) {
			list($interfaceName, $methodName) = $interfaceAndMethodName;
			if (!isset($interceptedMethods[$methodName])) {
				$interceptedMethods[$methodName]['groupedAdvices'] = array();
				$interceptedMethods[$methodName]['declaringClassName'] = $interfaceName;
			}
		}
	}

	/**
	 * Assures that a constructor exists, even though no advice exists for it.
	 * If a constructor had to be added, it will be added to the intercepted
	 * methods array.
	 *
	 * @param array &$interceptedMethods An array (empty or not) which contains the names of the intercepted methods and additional information
	 * @param $targetClassName Name of the class in question
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function addConstructorToInterceptedMethods(array &$interceptedMethods, $targetClassName) {
		if (!isset($interceptedMethods['__construct'])) {
			$declaringClassName = (method_exists($targetClassName, '__construct')) ? $targetClassName : NULL;
			$interceptedMethods['__construct']['groupedAdvices'] = array();
			$interceptedMethods['__construct']['declaringClassName'] = $declaringClassName;
		}
	}

	/**
	 * Traverses all aspect containers and returns an array of introductions
	 * which match the target class.
	 *
	 * @param array $aspectContainers All aspects to take into consideration
	 * @param  string $targetClassName Name of the class the pointcut should match with
	 * @return array array of interface names
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function getMatchingIntroductions(array $aspectContainers, $targetClassName) {
		$introductions = array();
		foreach ($aspectContainers as $aspectContainer) {
			foreach ($aspectContainer->getIntroductions() as $introduction) {
				$pointcut = $introduction->getPointcut();
				if ($pointcut->matches($targetClassName, NULL, NULL, uniqid())) {
					$introductions[] = $introduction;
				}
			}
		}
		return $introductions;
	}

	/**
	 * Returns an array of interface names introduced by the given introductions
	 *
	 * @param array $introductions An array of introductions
	 * @return array Array of interface names
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function getInterfaceNamesFromIntroductions(array $introductions) {
		$interfaceNames = array();
		foreach ($introductions as $introduction) {
			$interfaceNames[] = '\\' . $introduction->getInterfaceName();
		}
		return $interfaceNames;
	}

	/**
	 * Returns all methods declared by the introduced interfaces
	 *
	 * @param array $introductions An array of \F3\FLOW3\AOP\Introduction
	 * @return array An array of method information (interface, method name)
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function getIntroducedMethodsFromIntroductions(array $introductions) {
		$methods = array();
		$methodsAndIntroductions = array();
		foreach ($introductions as $introduction) {
			$interfaceName = $introduction->getInterfaceName();
			foreach (get_class_methods($interfaceName) as $newMethodName) {
				if (isset($methodsAndIntroductions[$newMethodName])) throw new \F3\FLOW3\AOP\Exception('Method name conflict! Method "' . $newMethodName . '" introduced by "' . $introduction->getInterfaceName() . '" declared in aspect "' . $introduction->getDeclaringAspectClassName() . '" has already been introduced by "' . $methodsAndIntroductions[$newMethodName]->getInterfaceName() . '" declared in aspect "' . $methodsAndIntroductions[$newMethodName]->getDeclaringAspectClassName() . '".', 1173020942);
				$methods[] = array($interfaceName, $newMethodName);
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
	 * @internal
	 */
	protected function getAdvicedMethodsInformation(array $interceptedMethods) {
		$advicedMethodsInformation = array();
		foreach ($interceptedMethods as $methodName => $interceptionInformation) {
			foreach ($interceptionInformation['groupedAdvices'] as $adviceType => $advices) {
				foreach ($advices as $advice) {
					$advicedMethodsInformation[$methodName][$adviceType][] = array (
						'aspectObjectName' => $advice->getAspectObjectName(),
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
	 * @param string $className Name of the class containing the annotations
	 * @return string PHP code snippet containing the annotations
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function buildClassAnnotationsCode($className) {
		$annotationsCode = '';
		foreach ($this->reflectionService->getClassTagsValues($className) as $tag => $values) {
			$annotationsCode .= ' * @' . $tag . ' ' . implode(' ', $values) . chr(10);
		}
		return $annotationsCode;
	}

	/**
	 * Renders a valid, unique class name for the proxy class
	 *
	 * @param string $targetClassName Name of the proxied class
	 * @param string $context The current application context
	 * @return string Name for the proxy class
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function renderProxyClassName($targetClassName, $context) {
		$targetClassNameArray = explode('\\', $targetClassName);
		$targetClassName = array_pop($targetClassNameArray);
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

	/**
	 * Extracts the namespace for the proxy class
	 *
	 * @param string $targetClassName Name of the proxied class
	 * @return string Name for the proxy namespace
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @internal
	 */
	protected function getProxyNamespace($targetClassName) {
		$targetClassNameArray = explode('\\', $targetClassName);
		array_pop($targetClassNameArray);
		return implode('\\', $targetClassNameArray);
	}
}
?>