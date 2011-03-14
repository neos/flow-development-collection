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

use \F3\FLOW3\Cache\CacheManager;

/**
 * The main class of the AOP (Aspect Oriented Programming) framework.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @proxy disable
 * @scope singleton
 */
class ProxyClassBuilder {

	/**
	 * @var \F3\FLOW3\Object\Proxy\Compiler
	 */
	protected $compiler;

	/**
	 * The FLOW3 settings
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * An instance of the pointcut expression parser
	 * @var \F3\FLOW3\AOP\Pointcut\PointcutExpressionParser
	 */
	protected $pointcutExpressionParser;

	/**
	 * @var \F3\FLOW3\AOP\Builder\ProxyClassBuilder
	 */
	protected $proxyClassBuilder;

	/**
	 * @var \F3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $targetClassInformationCache;

	/**
	 * @var \F3\FLOW3\Object\CompileTimeObjectManager
	 */
	protected $objectManager;

	/**
	 * Hardcoded list of FLOW3 sub packages (first 12 characters) which must be immune to AOP proxying for security, technical or conceptual reasons.
	 * @var array
	 */
	protected $blacklistedSubPackages = array('F3\FLOW3\AOP', 'F3\FLOW3\Cac', 'F3\FLOW3\Con', 'F3\FLOW3\Err', 'F3\FLOW3\Eve', 'F3\FLOW3\Loc', 'F3\FLOW3\Log', 'F3\FLOW3\Mon', 'F3\FLOW3\Obj', 'F3\FLOW3\Pac', 'F3\FLOW3\Pro', 'F3\FLOW3\Ref', 'F3\FLOW3\Uti', 'F3\FLOW3\Val');

	/**
	 * A registry of all known aspects
	 * @var array
	 */
	protected $aspectContainers = array();

	/**
	 * @var array
	 */
	protected $methodInterceptorBuilders = array();

	/**
	 * @param \F3\FLOW3\Object\Proxy\Compiler $compiler
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectCompiler(\F3\FLOW3\Object\Proxy\Compiler $compiler) {
		$this->compiler = $compiler;
	}

	/**
	 * Injects the reflection service
	 *
	 * @param \F3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \F3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSystemLogger(\F3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Injects an instance of the pointcut expression parser
	 *
	 * @param \F3\FLOW3\AOP\Pointcut\PointcutExpressionParser $pointcutExpressionParser
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPointcutExpressionParser(\F3\FLOW3\AOP\Pointcut\PointcutExpressionParser $pointcutExpressionParser) {
		$this->pointcutExpressionParser = $pointcutExpressionParser;
	}

	/**
	 * Injects the cache for storing information about target classes
	 *
	 * @param \F3\FLOW3\Cache\Frontend\VariableFrontend $targetClassInformationCache
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @autowiring off
	 */
	public function injectTargetClassInformationCache(\F3\FLOW3\Cache\Frontend\VariableFrontend $targetClassInformationCache) {
		$this->targetClassInformationCache = $targetClassInformationCache;
	}

	/**
	 * Injects the Empty Constructor Interceptor Builder
	 *
	 * @param \F3\FLOW3\AOP\Builder\EmptyConstructorInterceptorBuilder $builder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEmptyConstructorInterceptorBuilder(\F3\FLOW3\AOP\Builder\EmptyConstructorInterceptorBuilder $builder) {
		$this->methodInterceptorBuilders['EmptyConstructor'] = $builder;
	}

	/**
	 * Injects the Adviced Constructor Interceptor Builder
	 *
	 * @param \F3\FLOW3\AOP\Builder\AdvicedConstructorInterceptorBuilder $builder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectAdvicedConstructorInterceptorBuilder(\F3\FLOW3\AOP\Builder\AdvicedConstructorInterceptorBuilder $builder) {
		$this->methodInterceptorBuilders['AdvicedConstructor'] = $builder;
	}

	/**
	 * Injects the Empty Method Interceptor Builder
	 *
	 * @param \F3\FLOW3\AOP\Builder\EmptyMethodInterceptorBuilder $builder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEmptyMethodInterceptorBuilder(\F3\FLOW3\AOP\Builder\EmptyMethodInterceptorBuilder $builder) {
		$this->methodInterceptorBuilders['EmptyMethod'] = $builder;
	}

	/**
	 * Injects the Adviced Method Interceptor Builder
	 *
	 * @param \F3\FLOW3\AOP\Builder\AdvicedMethodInterceptorBuilder $builder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectAdvicedMethodInterceptorBuilder(\F3\FLOW3\AOP\Builder\AdvicedMethodInterceptorBuilder $builder) {
		$this->methodInterceptorBuilders['AdvicedMethod'] = $builder;
	}

	/**
	 * @param \F3\FLOW3\Object\CompileTimeObjectManager $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\CompileTimeObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the FLOW3 settings
	 *
	 * @param array $settings The settings
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Builds proxy class code which weaves advices into the respective target classes.
	 *
	 * The object configurations provided by the Compiler is searched for possible aspect
	 * annotations. If an aspect class is found, the poincut expressions are parsed and
	 * a new aspect with one or more advisors is added to the aspect registry of the AOP framework.
	 * Finally all advices are woven into their target classes by generating proxy classes.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function build() {
		$allAvailableClassNames = $this->objectManager->getRegisteredClassNames();
		$possibleTargetClassNames = $this->getProxyableClasses($allAvailableClassNames);
		$actualAspectClassNames = $this->reflectionService->getClassNamesByTag('aspect');
		sort($possibleTargetClassNames);
		sort($actualAspectClassNames);

		$this->aspectContainers = $this->buildAspectContainers($allAvailableClassNames);

		$rebuildEverything = FALSE;
		foreach (array_keys($this->aspectContainers) as $aspectClassName) {
			if ($this->compiler->hasCacheEntryForClass($aspectClassName) === FALSE) {
				$rebuildEverything = TRUE;
				$this->systemLogger->log(sprintf('Aspect %s has been modified, therefore rebuilding all target classes.', $aspectClassName), LOG_INFO);
				break;
			}
		}

		foreach ($possibleTargetClassNames as $targetClassName) {
			if ($rebuildEverything === TRUE || $this->compiler->hasCacheEntryForClass($targetClassName) === FALSE) {
				$proxyBuildResult = $this->buildProxyClass($targetClassName, $this->aspectContainers);
				if ($proxyBuildResult !== FALSE) {
					$this->systemLogger->log(sprintf('Built AOP proxy for class "%s".', $targetClassName), LOG_INFO);
				}
			}
		}
	}

	/**
	 * Traverses the aspect containers to find a pointcut from the aspect class name
	 * and pointcut method name
	 *
	 * @param string $aspectClassName Name of the aspect class where the pointcut has been declared
	 * @param string $pointcutMethodName Method name of the pointcut
	 * @return mixed The \F3\FLOW3\AOP\Pointcut\Pointcut or FALSE if none was found
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findPointcut($aspectClassName, $pointcutMethodName) {
		if (!isset($this->aspectContainers[$aspectClassName])) return FALSE;
		foreach ($this->aspectContainers[$aspectClassName]->getPointcuts() as $pointcut) {
			if ($pointcut->getPointcutMethodName() === $pointcutMethodName) {
				return $pointcut;
			}
		}
		return FALSE;
	}

	/**
	 * Returns an array of method names and advices which were applied to the specified class. If the
	 * target class has no adviced methods, an empty array is returned.
	 *
	 * @param string $targetClassName Name of the target class
	 * @return mixed An array of method names and their advices as array of \F3\FLOW3\AOP\Advice\AdviceInterface
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAdvicedMethodsInformationByTargetClass($targetClassName) {
		throw new \F3\FLOW3\AOP\Exception('This method is currently not supported.');
		if (!isset($this->advicedMethodsInformationByTargetClass[$targetClassName])) return array();
		return $this->advicedMethodsInformationByTargetClass[$targetClassName];
	}

	/**
	 * Determines which of the given classes are potentially proxyable
	 * and returns their names in an array.
	 *
	 * @param array $classNames Names of the classes to check
	 * @return array Names of classes which can be proxied
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getProxyableClasses(array $classNames) {
		$proxyableClasses = array();
		foreach ($classNames as $className) {
			if (!in_array(substr($className, 0, 12), $this->blacklistedSubPackages)) {
				if (!$this->reflectionService->isClassTaggedWith($className, 'aspect') &&
					!$this->reflectionService->isClassAbstract($className) &&
					!$this->reflectionService->isClassFinal($className)) {
					$proxyableClasses[] = $className;
				}
			}
		}
		return $proxyableClasses;
	}

	/**
	 * Checks the annotations of the specified classes for aspect tags
	 * and creates an aspect with advisors accordingly.
	 *
	 * @param array $classNames Classes to check for aspect tags.
	 * @return array An array of \F3\FLOW3\AOP\AspectContainer for all aspects which were found.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildAspectContainers($classNames) {
		$aspectContainers = array();
		foreach ($classNames as $aspectClassName) {
			if ($this->reflectionService->isClassReflected($aspectClassName) && $this->reflectionService->isClassTaggedWith($aspectClassName, 'aspect')) {
				$aspectContainers[$aspectClassName] =  $this->buildAspectContainer($aspectClassName);
			}
		}
		return $aspectContainers;
	}

	/**
	 * Creates and returns an aspect from the annotations found in a class which
	 * is tagged as an aspect. The object acting as an advice will already be
	 * fetched (and therefore instantiated if neccessary).
	 *
	 * @param  string $aspectClassName Name of the class which forms the aspect, contains advices etc.
	 * @return mixed The aspect container containing one or more advisors or FALSE if no container could be built
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildAspectContainer($aspectClassName) {
		$aspectContainer = new \F3\FLOW3\AOP\AspectContainer($aspectClassName);
		$methodNames = get_class_methods($aspectClassName);

		foreach ($methodNames as $methodName) {
			foreach ($this->reflectionService->getMethodTagsValues($aspectClassName, $methodName) as $tagName => $tagValues) {
				foreach ($tagValues as $tagValue) {
					switch ($tagName) {
						case 'around' :
							$pointcutFilterComposite = $this->pointcutExpressionParser->parse($tagValue, $this->renderSourceHint($aspectClassName, $methodName, $tagName));
							$advice = new \F3\FLOW3\AOP\Advice\AroundAdvice($aspectClassName, $methodName);
							$pointcut = new \F3\FLOW3\AOP\Pointcut\Pointcut($tagValue, $pointcutFilterComposite, $aspectClassName);
							$advisor = new \F3\FLOW3\AOP\Advisor($advice, $pointcut);
							$aspectContainer->addAdvisor($advisor);
						break;
						case 'before' :
							$pointcutFilterComposite = $this->pointcutExpressionParser->parse($tagValue, $this->renderSourceHint($aspectClassName, $methodName, $tagName));
							$advice = new \F3\FLOW3\AOP\Advice\BeforeAdvice($aspectClassName, $methodName);
							$pointcut = new \F3\FLOW3\AOP\Pointcut\Pointcut($tagValue, $pointcutFilterComposite, $aspectClassName);
							$advisor = new \F3\FLOW3\AOP\Advisor($advice, $pointcut);
							$aspectContainer->addAdvisor($advisor);
						break;
						case 'afterreturning' :
							$pointcutFilterComposite = $this->pointcutExpressionParser->parse($tagValue, $this->renderSourceHint($aspectClassName, $methodName, $tagName));
							$advice = new \F3\FLOW3\AOP\Advice\AfterReturningAdvice($aspectClassName, $methodName);
							$pointcut = new \F3\FLOW3\AOP\Pointcut\Pointcut($tagValue, $pointcutFilterComposite, $aspectClassName);
							$advisor = new \F3\FLOW3\AOP\Advisor($advice, $pointcut);
							$aspectContainer->addAdvisor($advisor);
						break;
						case 'afterthrowing' :
							$pointcutFilterComposite = $this->pointcutExpressionParser->parse($tagValue, $this->renderSourceHint($aspectClassName, $methodName, $tagName));
							$advice = new \F3\FLOW3\AOP\Advice\AfterThrowingAdvice($aspectClassName, $methodName);
							$pointcut = new \F3\FLOW3\AOP\Pointcut\Pointcut($tagValue, $pointcutFilterComposite, $aspectClassName);
							$advisor = new \F3\FLOW3\AOP\Advisor($advice, $pointcut);
							$aspectContainer->addAdvisor($advisor);
						break;
						case 'after' :
							$pointcutFilterComposite = $this->pointcutExpressionParser->parse($tagValue, $this->renderSourceHint($aspectClassName, $methodName, $tagName));
							$advice = new \F3\FLOW3\AOP\Advice\AfterAdvice($aspectClassName, $methodName);
							$pointcut = new \F3\FLOW3\AOP\Pointcut\Pointcut($tagValue, $pointcutFilterComposite, $aspectClassName);
							$advisor = new \F3\FLOW3\AOP\Advisor($advice, $pointcut);
							$aspectContainer->addAdvisor($advisor);
						break;
						case 'pointcut' :
							$pointcutFilterComposite = $this->pointcutExpressionParser->parse($tagValue, $this->renderSourceHint($aspectClassName, $methodName, $tagName));
							$pointcut = new \F3\FLOW3\AOP\Pointcut\Pointcut($tagValue, $pointcutFilterComposite, $aspectClassName, $methodName);
							$aspectContainer->addPointcut($pointcut);
						break;
					}
				}
			}
		}
		foreach ($this->reflectionService->getClassPropertyNames($aspectClassName) as $propertyName) {
			foreach ($this->reflectionService->getPropertyTagsValues($aspectClassName, $propertyName) as $tagName => $tagValues) {
				foreach ($tagValues as $tagValue) {
					switch ($tagName) {
						case 'introduce' :
							$splittedTagValue = explode(',', $tagValue);
							if (!is_array($splittedTagValue) || count($splittedTagValue) != 2)  throw new \F3\FLOW3\AOP\Exception('The introduction in class "' . $aspectClassName . '" does not contain the two required parameters.', 1172694761);
							$pointcutExpression = trim($splittedTagValue[1]);
							$pointcutFilterComposite = $this->pointcutExpressionParser->parse($pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $tagName));
							$pointcut = new \F3\FLOW3\AOP\Pointcut\Pointcut($pointcutExpression, $pointcutFilterComposite, $aspectClassName);
							$interfaceName = trim($splittedTagValue[0]);
							$introduction = new \F3\FLOW3\AOP\Introduction($aspectClassName, $interfaceName, $pointcut);
							$aspectContainer->addIntroduction($introduction);
						break;
					}
				}
			}
		}
		if (count($aspectContainer->getAdvisors()) < 1 && count($aspectContainer->getPointcuts()) < 1 && count($aspectContainer->getIntroductions()) < 1) throw new \F3\FLOW3\AOP\Exception('The class "' . $aspectClassName . '" is tagged to be an aspect but doesn\'t contain advices nor pointcut or introduction declarations.', 1169124534);
		return $aspectContainer;
	}

	/**
	 * Builds methods for a single AOP proxy class for the specified class.
	 *
	 * @param string $targetClassName Name of the class to create a proxy class file for
	 * @param array $aspectContainers The array of aspect containers from the AOP Framework
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildProxyClass($targetClassName, array $aspectContainers) {
		$introductions = $this->getMatchingIntroductions($aspectContainers, $targetClassName);
		$introducedInterfaces = $this->getInterfaceNamesFromIntroductions($introductions);

		$methodsFromTargetClass = $this->getMethodsFromTargetClass($targetClassName);
		$methodsFromIntroducedInterfaces = $this->getIntroducedMethodsFromIntroductions($introductions, $targetClassName);

		$interceptedMethods = array();
		$this->addAdvicedMethodsToInterceptedMethods($interceptedMethods, array_merge($methodsFromTargetClass, $methodsFromIntroducedInterfaces), $targetClassName, $aspectContainers);
		$this->addIntroducedMethodsToInterceptedMethods($interceptedMethods, $methodsFromIntroducedInterfaces);

		if (count($interceptedMethods) < 1 && count($introducedInterfaces) < 1) return FALSE;

		$proxyClass = $this->compiler->getProxyClass($targetClassName);
		if ($proxyClass === FALSE) {
			return;
		}

		$proxyClass->addInterfaces($introducedInterfaces);

		$proxyClass->getMethod('FLOW3_AOP_Proxy_buildMethodsAndAdvicesArray')->addPreParentCallCode($this->buildMethodsAndAdvicesArrayCode($interceptedMethods));
		$proxyClass->getConstructor()->addPreParentCallCode("\n\t\t\$this->FLOW3_AOP_Proxy_buildMethodsAndAdvicesArray();\n");
		$proxyClass->getMethod('__wakeup')->addPreParentCallCode("\n\t\t\$this->FLOW3_AOP_Proxy_buildMethodsAndAdvicesArray();\n");

		$this->buildGetAdviceChainsMethodCode($targetClassName);
		$this->buildInvokeJoinPointMethodCode($targetClassName);
		$this->buildGetPropertyMethodCode($targetClassName);
		$this->buildsetPropertyMethodCode($targetClassName);
		$this->buildMethodsInterceptorCode($targetClassName, $interceptedMethods);

		$proxyClass->addProperty('FLOW3_AOP_Proxy_targetMethodsAndGroupedAdvices', 'array()');
		$proxyClass->addProperty('FLOW3_AOP_Proxy_groupedAdviceChains', 'array()');
		$proxyClass->addProperty('FLOW3_AOP_Proxy_methodIsInAdviceMode', 'array()');
	}

	/**
	 * Returns the methods of the target class. If no constructor exists in the target class,
	 * it will nonetheless be added to the list of methods.
	 *
	 * @param string $targetClassName Name of the target class
	 * @return array Method information with declaring class and method name pairs
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getMethodsFromTargetClass($targetClassName) {
		$methods = array();
		$existingMethodNames = get_class_methods($targetClassName);

		if (array_search('__construct', $existingMethodNames) === FALSE) $methods[] = array(NULL, '__construct');
		foreach ($existingMethodNames as $methodName) {
			$methods[] = array($targetClassName, $methodName);
		}

		return $methods;
	}

	/**
	 * Creates code for an array of target methods and their advices.
	 *
	 * Example:
	 *
	 *	$this->FLOW3_AOP_Proxy_targetMethodsAndGroupedAdvices = array(
	 *		'getSomeProperty' => array(
	 *			'F3\FLOW3\AOP\Advice\AroundAdvice' => array(
	 *				new \F3\FLOW3\AOP\Advice\AroundAdvice('F3\Foo\SomeAspect', 'aroundAdvice', \\F3\\FLOW3\\Core\\Bootstrap::$staticObjectManager, function() { ... }),
	 *			),
	 *		),
	 *	);
	 *
	 *
	 * @param array $methodsAndGroupedAdvices An array of method names and grouped advice objects
	 * @return string PHP code for the content of an array of target method names and advice objects
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @see buildProxyClass()
	 */
	protected function buildMethodsAndAdvicesArrayCode(array $methodsAndGroupedAdvices) {
		if (count($methodsAndGroupedAdvices) < 1) return '';

		$methodsAndAdvicesArrayCode = "\n\t\t\$objectManager = \\F3\\FLOW3\\Core\\Bootstrap::\$staticObjectManager;\n";
		$methodsAndAdvicesArrayCode .= "\t\t\$this->FLOW3_AOP_Proxy_targetMethodsAndGroupedAdvices = array(\n";
		foreach ($methodsAndGroupedAdvices as $methodName => $advicesAndDeclaringClass) {
			$methodsAndAdvicesArrayCode .= "\t\t\t'" . $methodName . "' => array(\n";
			foreach ($advicesAndDeclaringClass['groupedAdvices'] as $adviceType => $advices) {
				$methodsAndAdvicesArrayCode .= "\t\t\t\t'" . $adviceType . "' => array(\n";
				foreach ($advices as $advice) {
					$methodsAndAdvicesArrayCode .= "\t\t\t\t\tnew \\" . get_class($advice) . "('" . $advice->getAspectObjectName() . "', '" . $advice->getAdviceMethodName() . "', \$objectManager, " . $methodsAndGroupedAdvices[$methodName]['runtimeEvaluationsClosureCode'] . "),\n";
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
	 * The generated code is added directly to the proxy class by calling the respective
	 * methods of the Compiler API.
	 *
	 * @param string $targetClassName The target class the pointcut should match with
	 * @param array $interceptedMethods An array of method names which need to be intercepted
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildMethodsInterceptorCode($targetClassName, array $interceptedMethods) {
		foreach ($interceptedMethods as $methodName => $methodMetaInformation) {
			$hasAdvices = (count($methodMetaInformation['groupedAdvices']) > 0);
			$builderType = ($hasAdvices ? 'Adviced' : 'Empty') . ($methodName === '__construct' ? 'Constructor' : 'Method');
			$this->methodInterceptorBuilders[$builderType]->build($methodName, $interceptedMethods, $targetClassName);
		}
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
	 */
	protected function addAdvicedMethodsToInterceptedMethods(array &$interceptedMethods, array $methods, $targetClassName, array $aspectContainers) {
		$pointcutQueryIdentifier = 0;

		foreach ($aspectContainers as $aspectContainer) {
			foreach ($aspectContainer->getAdvisors() as $advisor) {
				$pointcut = $advisor->getPointcut();
				foreach ($methods as $method) {
					list($methodDeclaringClassName, $methodName) = $method;

					if ($this->reflectionService->isMethodFinal($targetClassName, $methodName)) continue;

					if ($pointcut->matches($targetClassName, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)) {
						$advice = $advisor->getAdvice();
						$interceptedMethods[$methodName]['groupedAdvices'][get_class($advice)][] = $advice;
						$interceptedMethods[$methodName]['declaringClassName'] = $methodDeclaringClassName;
						$interceptedMethods[$methodName]['runtimeEvaluationsClosureCode'] = $pointcut->getRuntimeEvaluationsClosureCode();
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
	 * Traverses all aspect containers and returns an array of introductions
	 * which match the target class.
	 *
	 * @param array $aspectContainers All aspects to take into consideration
	 * @param string $targetClassName Name of the class the pointcut should match with
	 * @return array array of interface names
	 * @author Robert Lemke <robert@typo3.org>
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
	 * Adds a "getAdviceChains()" method to the current proxy class.
	 *
	 * @param  $targetClassName
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildGetAdviceChainsMethodCode($targetClassName) {
		$proxyMethod = $this->compiler->getProxyClass($targetClassName)->getMethod('FLOW3_AOP_Proxy_getAdviceChains');
		$proxyMethod->setMethodParametersCode('$methodName');

		$code = <<<'EOT'
		$adviceChains = NULL;
		if (is_array($this->FLOW3_AOP_Proxy_groupedAdviceChains)) {
			if (isset($this->FLOW3_AOP_Proxy_groupedAdviceChains[$methodName])) {
				$adviceChains = $this->FLOW3_AOP_Proxy_groupedAdviceChains[$methodName];
			} else {
				if (isset($this->FLOW3_AOP_Proxy_targetMethodsAndGroupedAdvices[$methodName])) {
					$groupedAdvices = $this->FLOW3_AOP_Proxy_targetMethodsAndGroupedAdvices[$methodName];
					if (isset($groupedAdvices['F3\FLOW3\AOP\Advice\AroundAdvice'])) {
						$this->FLOW3_AOP_Proxy_groupedAdviceChains[$methodName]['F3\FLOW3\AOP\Advice\AroundAdvice'] = new \F3\FLOW3\AOP\Advice\AdviceChain($groupedAdvices['F3\FLOW3\AOP\Advice\AroundAdvice'], $this);
						$adviceChains = $this->FLOW3_AOP_Proxy_groupedAdviceChains[$methodName];
					}
				}
			}
		}
		return $adviceChains;

EOT;
		$proxyMethod->addPreParentCallCode($code);
	}

	/**
	 * Adds a "invokeJoinPoint()" method to the current proxy class.
	 *
	 * @param  $targetClassName
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildInvokeJoinPointMethodCode($targetClassName) {
		$proxyMethod = $this->compiler->getProxyClass($targetClassName)->getMethod('FLOW3_AOP_Proxy_invokeJoinPoint');
		$proxyMethod->setMethodParametersCode('\F3\FLOW3\AOP\JoinPointInterface $joinPoint');
		$code = <<<'EOT'
		if (isset($this->FLOW3_AOP_Proxy_methodIsInAdviceMode[$joinPoint->getMethodName()])) {
			return call_user_func_array(array($this, $joinPoint->getMethodName()), $joinPoint->getMethodArguments());
		}

EOT;
		$proxyMethod->addPreParentCallCode($code);
	}

	/**
	 *
	 *
	 * @param  $targetClassName
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @FIXME The FLOW3_AOP_Proxy_getProperty method should be removed altogether as soon as nothing else depends on it
	 */
	protected function buildGetPropertyMethodCode($targetClassName) {
		$proxyMethod = $this->compiler->getProxyClass($targetClassName)->getMethod('FLOW3_AOP_Proxy_getProperty');
		$proxyMethod->setMethodParametersCode('$name');
		$code = <<<'EOT'
		return $this->$name;

EOT;
		$proxyMethod->addPreParentCallCode($code);
	}

	/**
	 *
	 *
	 * @param  $targetClassName
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @FIXME The FLOW3_AOP_Proxy_setProperty method should be removed altogether as soon as nothing else depends on it
	 */
	protected function buildSetPropertyMethodCode($targetClassName) {
		$proxyMethod = $this->compiler->getProxyClass($targetClassName)->getMethod('FLOW3_AOP_Proxy_setProperty');
		$proxyMethod->setMethodParametersCode('$name, $value');
		$code = <<<'EOT'
		$this->$name = $value;

EOT;
		$proxyMethod->addPreParentCallCode($code);
	}

	/**
	 * Renders a short message which gives a hint on where the currently parsed pointcut expression was defined.
	 *
	 * @return void
	 */
	protected function renderSourceHint($aspectClassName, $methodName, $tagName) {
		return sprintf('%s::%s (%s advice)', $aspectClassName, $methodName, $tagName);
	}
}
?>