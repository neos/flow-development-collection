<?php
namespace TYPO3\Flow\Aop\Builder;

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

/**
 * The main class of the AOP (Aspect Oriented Programming) framework.
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class ProxyClassBuilder {

	/**
	 * @var \TYPO3\Flow\Object\Proxy\Compiler
	 */
	protected $compiler;

	/**
	 * The Flow settings
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * An instance of the pointcut expression parser
	 * @var \TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser
	 */
	protected $pointcutExpressionParser;

	/**
	 * @var \TYPO3\Flow\Aop\Builder\ProxyClassBuilder
	 */
	protected $proxyClassBuilder;

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $objectConfigurationCache;

	/**
	 * @var \TYPO3\Flow\Object\CompileTimeObjectManager
	 */
	protected $objectManager;

	/**
	 * Hardcoded list of Flow sub packages (first 15 characters) which must be immune to AOP proxying for security, technical or conceptual reasons.
	 * @var array
	 */
	protected $blacklistedSubPackages = array('TYPO3\Flow\Aop\\', 'TYPO3\Flow\Cach', 'TYPO3\Flow\Erro', 'TYPO3\Flow\Log\\', 'TYPO3\Flow\Moni', 'TYPO3\Flow\Obje', 'TYPO3\Flow\Pack', 'TYPO3\Flow\Prop', 'TYPO3\Flow\Refl', 'TYPO3\Flow\Util', 'TYPO3\Flow\Vali');

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
	 * @param \TYPO3\Flow\Object\Proxy\Compiler $compiler
	 * @return void
	 */
	public function injectCompiler(\TYPO3\Flow\Object\Proxy\Compiler $compiler) {
		$this->compiler = $compiler;
	}

	/**
	 * Injects the reflection service
	 *
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \TYPO3\Flow\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 */
	public function injectSystemLogger(\TYPO3\Flow\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Injects an instance of the pointcut expression parser
	 *
	 * @param \TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser $pointcutExpressionParser
	 * @return void
	 */
	public function injectPointcutExpressionParser(\TYPO3\Flow\Aop\Pointcut\PointcutExpressionParser $pointcutExpressionParser) {
		$this->pointcutExpressionParser = $pointcutExpressionParser;
	}

	/**
	 * Injects the cache for storing information about objects
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\VariableFrontend $objectConfigurationCache
	 * @return void
	 * @Flow\Autowiring(false)
	 */
	public function injectObjectConfigurationCache(\TYPO3\Flow\Cache\Frontend\VariableFrontend $objectConfigurationCache) {
		$this->objectConfigurationCache = $objectConfigurationCache;
	}

	/**
	 * Injects the Adviced Constructor Interceptor Builder
	 *
	 * @param \TYPO3\Flow\Aop\Builder\AdvicedConstructorInterceptorBuilder $builder
	 * @return void
	 */
	public function injectAdvicedConstructorInterceptorBuilder(\TYPO3\Flow\Aop\Builder\AdvicedConstructorInterceptorBuilder $builder) {
		$this->methodInterceptorBuilders['AdvicedConstructor'] = $builder;
	}

	/**
	 * Injects the Adviced Method Interceptor Builder
	 *
	 * @param \TYPO3\Flow\Aop\Builder\AdvicedMethodInterceptorBuilder $builder
	 * @return void
	 */
	public function injectAdvicedMethodInterceptorBuilder(\TYPO3\Flow\Aop\Builder\AdvicedMethodInterceptorBuilder $builder) {
		$this->methodInterceptorBuilders['AdvicedMethod'] = $builder;
	}

	/**
	 * @param \TYPO3\Flow\Object\CompileTimeObjectManager $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\Flow\Object\CompileTimeObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the Flow settings
	 *
	 * @param array $settings The settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Builds proxy class code which weaves advices into the respective target classes.
	 *
	 * The object configurations provided by the Compiler are searched for possible aspect
	 * annotations. If an aspect class is found, the poincut expressions are parsed and
	 * a new aspect with one or more advisors is added to the aspect registry of the AOP framework.
	 * Finally all advices are woven into their target classes by generating proxy classes.
	 *
	 * In general, the command typo3.flow:core:compile is responsible for compilation
	 * and calls this method to do so.
	 *
	 * In order to distinguish between an emerged / changed possible target class and
	 * a class which has been matched previously but just didn't have to be proxied,
	 * the latter are kept track of by an "unproxiedClass-*" cache entry.
	 *
	 * @return void
	 */
	public function build() {
		$allAvailableClassNamesByPackage = $this->objectManager->getRegisteredClassNames();
		$possibleTargetClassNames = $this->getProxyableClasses($allAvailableClassNamesByPackage);
		$actualAspectClassNames = $this->reflectionService->getClassNamesByAnnotation('TYPO3\Flow\Annotations\Aspect');
		sort($possibleTargetClassNames);
		sort($actualAspectClassNames);

		$this->aspectContainers = $this->buildAspectContainers($actualAspectClassNames);

		$rebuildEverything = FALSE;
		if ($this->objectConfigurationCache->has('allAspectClassesUpToDate') === FALSE) {
			$rebuildEverything = TRUE;
			$this->systemLogger->log('Aspects have been modified, therefore rebuilding all target classes.', LOG_INFO);
			$this->objectConfigurationCache->set('allAspectClassesUpToDate', TRUE);
		}

		$possibleTargetClassNameIndex = new ClassNameIndex();
		$possibleTargetClassNameIndex->setClassNames($possibleTargetClassNames);

		$targetClassNameCandidates = new ClassNameIndex();
		foreach ($this->aspectContainers as $aspectContainer) {
			$targetClassNameCandidates->applyUnion($aspectContainer->reduceTargetClassNames($possibleTargetClassNameIndex));
		}
		$targetClassNameCandidates->sort();

		foreach ($targetClassNameCandidates->getClassNames() as $targetClassName) {
			$isUnproxied = $this->objectConfigurationCache->has('unproxiedClass-' . str_replace('\\', '_', $targetClassName));
			$hasCacheEntry = $this->compiler->hasCacheEntryForClass($targetClassName) || $isUnproxied;
			if ($rebuildEverything === TRUE || $hasCacheEntry === FALSE) {
				$proxyBuildResult = $this->buildProxyClass($targetClassName, $this->aspectContainers);
				if ($proxyBuildResult !== FALSE) {
					if ($isUnproxied) {
						$this->objectConfigurationCache->remove('unproxiedClass-' . str_replace('\\', '_', $targetClassName));
					}
					$this->systemLogger->log(sprintf('Built AOP proxy for class "%s".', $targetClassName), LOG_DEBUG);
				} else {
					$this->objectConfigurationCache->set('unproxiedClass-' . str_replace('\\', '_', $targetClassName), TRUE);
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
	 * @return mixed The \TYPO3\Flow\Aop\Pointcut\Pointcut or FALSE if none was found
	 */
	public function findPointcut($aspectClassName, $pointcutMethodName) {
		if (!isset($this->aspectContainers[$aspectClassName])) {
			return FALSE;
		}
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
	 * @return mixed An array of method names and their advices as array of \TYPO3\Flow\Aop\Advice\AdviceInterface
	 * @throws \TYPO3\Flow\Aop\Exception
	 */
	public function getAdvicedMethodsInformationByTargetClass($targetClassName) {
		throw new \TYPO3\Flow\Aop\Exception('This method is currently not supported.');

		if (!isset($this->advicedMethodsInformationByTargetClass[$targetClassName])) {
			return array();
		} else {
			return $this->advicedMethodsInformationByTargetClass[$targetClassName];
		}
	}

	/**
	 * Determines which of the given classes are potentially proxyable
	 * and returns their names in an array.
	 *
	 * @param array $classNamesByPackage Names of the classes to check
	 * @return array Names of classes which can be proxied
	 */
	protected function getProxyableClasses(array $classNamesByPackage) {
		$proxyableClasses = array();
		foreach ($classNamesByPackage as $classNames) {
			foreach ($classNames as $className) {
				if (!in_array(substr($className, 0, 15), $this->blacklistedSubPackages)) {
					if (!$this->reflectionService->isClassAnnotatedWith($className, 'TYPO3\Flow\Annotations\Aspect') &&
						!$this->reflectionService->isClassFinal($className)) {
						$proxyableClasses[] = $className;
					}
				}
			}
		}
		return $proxyableClasses;
	}

	/**
	 * Checks the annotations of the specified classes for aspect tags
	 * and creates an aspect with advisors accordingly.
	 *
	 * @param array &$classNames Classes to check for aspect tags.
	 * @return array An array of \TYPO3\Flow\Aop\AspectContainer for all aspects which were found.
	 */
	protected function buildAspectContainers(array &$classNames) {
		$aspectContainers = array();
		foreach ($classNames as $aspectClassName) {
			$aspectContainers[$aspectClassName] = $this->buildAspectContainer($aspectClassName);
		}
		return $aspectContainers;
	}

	/**
	 * Creates and returns an aspect from the annotations found in a class which
	 * is tagged as an aspect. The object acting as an advice will already be
	 * fetched (and therefore instantiated if necessary).
	 *
	 * @param  string $aspectClassName Name of the class which forms the aspect, contains advices etc.
	 * @return mixed The aspect container containing one or more advisors or FALSE if no container could be built
	 * @throws \TYPO3\Flow\Aop\Exception
	 */
	protected function buildAspectContainer($aspectClassName) {
		$aspectContainer = new \TYPO3\Flow\Aop\AspectContainer($aspectClassName);
		$methodNames = get_class_methods($aspectClassName);

		foreach ($methodNames as $methodName) {
			foreach ($this->reflectionService->getMethodAnnotations($aspectClassName, $methodName) as $annotation) {
				$annotationClass = get_class($annotation);
				switch ($annotationClass) {
					case 'TYPO3\Flow\Annotations\Around' :
						$pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
						$advice = new \TYPO3\Flow\Aop\Advice\AroundAdvice($aspectClassName, $methodName);
						$pointcut = new \TYPO3\Flow\Aop\Pointcut\Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
						$advisor = new \TYPO3\Flow\Aop\Advisor($advice, $pointcut);
						$aspectContainer->addAdvisor($advisor);
					break;
					case 'TYPO3\Flow\Annotations\Before' :
						$pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
						$advice = new \TYPO3\Flow\Aop\Advice\BeforeAdvice($aspectClassName, $methodName);
						$pointcut = new \TYPO3\Flow\Aop\Pointcut\Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
						$advisor = new \TYPO3\Flow\Aop\Advisor($advice, $pointcut);
						$aspectContainer->addAdvisor($advisor);
					break;
					case 'TYPO3\Flow\Annotations\AfterReturning' :
						$pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
						$advice = new \TYPO3\Flow\Aop\Advice\AfterReturningAdvice($aspectClassName, $methodName);
						$pointcut = new \TYPO3\Flow\Aop\Pointcut\Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
						$advisor = new \TYPO3\Flow\Aop\Advisor($advice, $pointcut);
						$aspectContainer->addAdvisor($advisor);
					break;
					case 'TYPO3\Flow\Annotations\AfterThrowing' :
						$pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
						$advice = new \TYPO3\Flow\Aop\Advice\AfterThrowingAdvice($aspectClassName, $methodName);
						$pointcut = new \TYPO3\Flow\Aop\Pointcut\Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
						$advisor = new \TYPO3\Flow\Aop\Advisor($advice, $pointcut);
						$aspectContainer->addAdvisor($advisor);
					break;
					case 'TYPO3\Flow\Annotations\After' :
						$pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
						$advice = new \TYPO3\Flow\Aop\Advice\AfterAdvice($aspectClassName, $methodName);
						$pointcut = new \TYPO3\Flow\Aop\Pointcut\Pointcut($annotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
						$advisor = new \TYPO3\Flow\Aop\Advisor($advice, $pointcut);
						$aspectContainer->addAdvisor($advisor);
					break;
					case 'TYPO3\Flow\Annotations\Pointcut' :
						$pointcutFilterComposite = $this->pointcutExpressionParser->parse($annotation->expression, $this->renderSourceHint($aspectClassName, $methodName, $annotationClass));
						$pointcut = new \TYPO3\Flow\Aop\Pointcut\Pointcut($annotation->expression, $pointcutFilterComposite, $aspectClassName, $methodName);
						$aspectContainer->addPointcut($pointcut);
					break;
				}
			}
		}
		$introduceAnnotation = $this->reflectionService->getClassAnnotation($aspectClassName, 'TYPO3\Flow\Annotations\Introduce');
		if ($introduceAnnotation !== NULL) {
			if ($introduceAnnotation->interfaceName === NULL) {
				throw new \TYPO3\Flow\Aop\Exception('The interface introduction in class "' . $aspectClassName . '" does not contain the required interface name).', 1172694761);
			}
			$pointcutFilterComposite = $this->pointcutExpressionParser->parse($introduceAnnotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $introduceAnnotation->interfaceName, 'TYPO3\Flow\Annotations\Introduce'));
			$pointcut = new \TYPO3\Flow\Aop\Pointcut\Pointcut($introduceAnnotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
			$introduction = new \TYPO3\Flow\Aop\InterfaceIntroduction($aspectClassName, $introduceAnnotation->interfaceName, $pointcut);
			$aspectContainer->addInterfaceIntroduction($introduction);
		}

		foreach ($this->reflectionService->getClassPropertyNames($aspectClassName) as $propertyName) {
			$introduceAnnotation = $this->reflectionService->getPropertyAnnotation($aspectClassName, $propertyName, 'TYPO3\Flow\Annotations\Introduce');
			if ($introduceAnnotation !== NULL) {
				$pointcutFilterComposite = $this->pointcutExpressionParser->parse($introduceAnnotation->pointcutExpression, $this->renderSourceHint($aspectClassName, $propertyName, 'TYPO3\Flow\Annotations\Introduce'));
				$pointcut = new \TYPO3\Flow\Aop\Pointcut\Pointcut($introduceAnnotation->pointcutExpression, $pointcutFilterComposite, $aspectClassName);
				$introduction = new \TYPO3\Flow\Aop\PropertyIntroduction($aspectClassName, $propertyName, $pointcut);
				$aspectContainer->addPropertyIntroduction($introduction);
			}
		}
		if (count($aspectContainer->getAdvisors()) < 1 &&
			count($aspectContainer->getPointcuts()) < 1 &&
			count($aspectContainer->getInterfaceIntroductions()) < 1 &&
			count($aspectContainer->getPropertyIntroductions()) < 1) {
			throw new \TYPO3\Flow\Aop\Exception('The class "' . $aspectClassName . '" is tagged to be an aspect but doesn\'t contain advices nor pointcut or introduction declarations.', 1169124534);
		}
		return $aspectContainer;
	}

	/**
	 * Builds methods for a single AOP proxy class for the specified class.
	 *
	 * @param string $targetClassName Name of the class to create a proxy class file for
	 * @param array &$aspectContainers The array of aspect containers from the AOP Framework
	 * @return boolean TRUE if the proxy class could be built, FALSE otherwise.
	 */
	public function buildProxyClass($targetClassName, array &$aspectContainers) {
		$interfaceIntroductions = $this->getMatchingInterfaceIntroductions($aspectContainers, $targetClassName);
		$introducedInterfaces = $this->getInterfaceNamesFromIntroductions($interfaceIntroductions);

		$propertyIntroductions = $this->getMatchingPropertyIntroductions($aspectContainers, $targetClassName);

		$methodsFromTargetClass = $this->getMethodsFromTargetClass($targetClassName);
		$methodsFromIntroducedInterfaces = $this->getIntroducedMethodsFromInterfaceIntroductions($interfaceIntroductions, $targetClassName);

		$interceptedMethods = array();
		$this->addAdvicedMethodsToInterceptedMethods($interceptedMethods, array_merge($methodsFromTargetClass, $methodsFromIntroducedInterfaces), $targetClassName, $aspectContainers);
		$this->addIntroducedMethodsToInterceptedMethods($interceptedMethods, $methodsFromIntroducedInterfaces);

		if (count($interceptedMethods) < 1 && count($introducedInterfaces) < 1 && count($propertyIntroductions) < 1) {
			return FALSE;
		}

		$proxyClass = $this->compiler->getProxyClass($targetClassName);
		if ($proxyClass === FALSE) {
			return FALSE;
		}

		$proxyClass->addInterfaces($introducedInterfaces);

		foreach ($propertyIntroductions as $propertyIntroduction) {
			$proxyClass->addProperty($propertyIntroduction->getPropertyName(), 'NULL', $propertyIntroduction->getPropertyVisibility(), $propertyIntroduction->getPropertyDocComment());
		}

		$proxyClass->getMethod('Flow_Aop_Proxy_buildMethodsAndAdvicesArray')->addPreParentCallCode("\t\tif (method_exists(get_parent_class(\$this), 'Flow_Aop_Proxy_buildMethodsAndAdvicesArray') && is_callable('parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray')) parent::Flow_Aop_Proxy_buildMethodsAndAdvicesArray();\n");
		$proxyClass->getMethod('Flow_Aop_Proxy_buildMethodsAndAdvicesArray')->addPreParentCallCode($this->buildMethodsAndAdvicesArrayCode($interceptedMethods));
		$proxyClass->getMethod('Flow_Aop_Proxy_buildMethodsAndAdvicesArray')->overrideMethodVisibility('protected');

		$callBuildMethodsAndAdvicesArrayCode = "\n\t\t\$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();\n";
		$proxyClass->getConstructor()->addPreParentCallCode($callBuildMethodsAndAdvicesArrayCode);
		$proxyClass->getMethod('__wakeup')->addPreParentCallCode($callBuildMethodsAndAdvicesArrayCode);

		if (!$this->reflectionService->hasMethod($targetClassName, '__wakeup')) {
			$proxyClass->getMethod('__wakeup')->addPostParentCallCode("\t\tif (method_exists(get_parent_class(\$this), '__wakeup') && is_callable('parent::__wakeup')) parent::__wakeup();\n");
		}

			// FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
		$proxyClass->getMethod('Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies')->addPreParentCallCode($this->fixMethodsAndAdvicesArrayForDoctrineProxiesCode());
			// FIXME this can be removed again once Doctrine is fixed (see fixInjectedPropertiesForDoctrineProxiesCode())
		$proxyClass->getMethod('Flow_Aop_Proxy_fixInjectedPropertiesForDoctrineProxies')->addPreParentCallCode($this->fixInjectedPropertiesForDoctrineProxiesCode());

		$this->buildGetAdviceChainsMethodCode($targetClassName);
		$this->buildInvokeJoinPointMethodCode($targetClassName);
		$this->buildMethodsInterceptorCode($targetClassName, $interceptedMethods);

		$proxyClass->addProperty('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'array()');
		$proxyClass->addProperty('Flow_Aop_Proxy_groupedAdviceChains', 'array()');
		$proxyClass->addProperty('Flow_Aop_Proxy_methodIsInAdviceMode', 'array()');

		return TRUE;
	}

	/**
	 * Returns the methods of the target class.
	 *
	 * @param string $targetClassName Name of the target class
	 * @return array Method information with declaring class and method name pairs
	 */
	protected function getMethodsFromTargetClass($targetClassName) {
		$methods = array();
		$class = new \ReflectionClass($targetClassName);

		foreach (array('__construct', '__clone') as $builtInMethodName) {
			if (!$class->hasMethod($builtInMethodName)) {
				$methods[] = array($targetClassName, $builtInMethodName);
			}
		}

		foreach ($class->getMethods() as $method) {
			$methods[] = array($targetClassName, $method->getName());
		}

		return $methods;
	}

	/**
	 * Creates code for an array of target methods and their advices.
	 *
	 * Example:
	 *
	 *	$this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array(
	 *		'getSomeProperty' => array(
	 *			'TYPO3\Flow\Aop\Advice\AroundAdvice' => array(
	 *				new \TYPO3\Flow\Aop\Advice\AroundAdvice('TYPO3\Foo\SomeAspect', 'aroundAdvice', \\TYPO3\\Flow\\Core\\Bootstrap::$staticObjectManager, function() { ... }),
	 *			),
	 *		),
	 *	);
	 *
	 *
	 * @param array $methodsAndGroupedAdvices An array of method names and grouped advice objects
	 * @return string PHP code for the content of an array of target method names and advice objects
	 * @see buildProxyClass()
	 */
	protected function buildMethodsAndAdvicesArrayCode(array $methodsAndGroupedAdvices) {
		if (count($methodsAndGroupedAdvices) < 1) {
			return '';
		}

		$methodsAndAdvicesArrayCode = "\n\t\t\$objectManager = \\TYPO3\\Flow\\Core\\Bootstrap::\$staticObjectManager;\n";
		$methodsAndAdvicesArrayCode .= "\t\t\$this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices = array(\n";
		foreach ($methodsAndGroupedAdvices as $methodName => $advicesAndDeclaringClass) {
			$methodsAndAdvicesArrayCode .= "\t\t\t'" . $methodName . "' => array(\n";
			foreach ($advicesAndDeclaringClass['groupedAdvices'] as $adviceType => $adviceConfigurations) {
				$methodsAndAdvicesArrayCode .= "\t\t\t\t'" . $adviceType . "' => array(\n";
				foreach ($adviceConfigurations as $adviceConfiguration) {
					$advice = $adviceConfiguration['advice'];
					$methodsAndAdvicesArrayCode .= "\t\t\t\t\tnew \\" . get_class($advice) . "('" . $advice->getAspectObjectName() . "', '" . $advice->getAdviceMethodName() . "', \$objectManager, " . $adviceConfiguration['runtimeEvaluationsClosureCode'] . "),\n";
				}
				$methodsAndAdvicesArrayCode .= "\t\t\t\t),\n";
			}
			$methodsAndAdvicesArrayCode .= "\t\t\t),\n";
		}
		$methodsAndAdvicesArrayCode .= "\t\t);\n";
		return  $methodsAndAdvicesArrayCode;
	}

	/**
	 * Creates code that builds the targetMethodsAndGroupedAdvices array if it does not exist. This happens when a Doctrine
	 * lazy loading proxy for an object is created for some specific purpose, but filled afterwards "on the fly" if this object
	 * is part of a wide range "findBy" query.
	 *
	 * @todo Remove once doctrine is fixed
	 * @return string
	 */
	protected function fixMethodsAndAdvicesArrayForDoctrineProxiesCode() {
		$code = <<<EOT
		if (!isset(\$this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices) || empty(\$this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices)) {
			\$this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
			if (is_callable('parent::Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies')) parent::Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
		}
EOT;
		return $code;
	}

	/**
	 * Creates code that reinjects dependencies if they do not exist. This is necessary because in certain circumstances
	 * Doctrine loads a proxy in UnitOfWork->createEntity() without calling __wakeup and thus does not initialize DI.
	 * This happens when a Doctrine lazy loading proxy for an object is created for some specific purpose, but filled
	 * afterwards "on the fly" if this object is part of a wide range "findBy" query.
	 *
	 * @todo Remove once doctrine is fixed
	 * @return string
	 */
	protected function fixInjectedPropertiesForDoctrineProxiesCode() {
		$code = <<<EOT
		if (!\$this instanceof \Doctrine\ORM\Proxy\Proxy || isset(\$this->Flow_Proxy_injectProperties_fixInjectedPropertiesForDoctrineProxies)) {
			return;
		}
		\$this->Flow_Proxy_injectProperties_fixInjectedPropertiesForDoctrineProxies = TRUE;
		if (is_callable(array(\$this, 'Flow_Proxy_injectProperties'))) {
			\$this->Flow_Proxy_injectProperties();
		}
EOT;
		return $code;
	}

	/**
	 * Traverses all intercepted methods and their advices and builds PHP code to intercept
	 * methods if necessary.
	 *
	 * The generated code is added directly to the proxy class by calling the respective
	 * methods of the Compiler API.
	 *
	 * @param string $targetClassName The target class the pointcut should match with
	 * @param array $interceptedMethods An array of method names which need to be intercepted
	 * @return void
	 * @throws \TYPO3\Flow\Aop\Exception\VoidImplementationException
	 */
	protected function buildMethodsInterceptorCode($targetClassName, array $interceptedMethods) {
		foreach ($interceptedMethods as $methodName => $methodMetaInformation) {
			if (count($methodMetaInformation['groupedAdvices']) === 0) {
				throw new \TYPO3\Flow\Aop\Exception\VoidImplementationException(sprintf('Refuse to introduce method %s into target class %s because it has no implementation code. You might want to create an around advice which implements this method.', $methodName, $targetClassName), 1303224472);
			}
			$builderType = 'Adviced' . ($methodName === '__construct' ? 'Constructor' : 'Method');
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
	 * @param array &$aspectContainers All aspects to take into consideration
	 * @return void
	 */
	protected function addAdvicedMethodsToInterceptedMethods(array &$interceptedMethods, array $methods, $targetClassName, array &$aspectContainers) {
		$pointcutQueryIdentifier = 0;

		foreach ($aspectContainers as $aspectContainer) {
			if (!$aspectContainer->getCachedTargetClassNameCandidates()->hasClassName($targetClassName)) {
				continue;
			}
			foreach ($aspectContainer->getAdvisors() as $advisor) {
				$pointcut = $advisor->getPointcut();
				foreach ($methods as $method) {
					list($methodDeclaringClassName, $methodName) = $method;

					if ($this->reflectionService->isMethodFinal($targetClassName, $methodName)) {
						continue;
					}

					if ($this->reflectionService->isMethodStatic($targetClassName, $methodName)) {
						continue;
					}

					if ($pointcut->matches($targetClassName, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)) {
						$advice = $advisor->getAdvice();
						$interceptedMethods[$methodName]['groupedAdvices'][get_class($advice)][] = array(
							'advice' => $advice,
							'runtimeEvaluationsClosureCode' => $pointcut->getRuntimeEvaluationsClosureCode()
						);
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
	 * Traverses all aspect containers and returns an array of interface
	 * introductions which match the target class.
	 *
	 * @param array &$aspectContainers All aspects to take into consideration
	 * @param string $targetClassName Name of the class the pointcut should match with
	 * @return array array of interface names
	 */
	protected function getMatchingInterfaceIntroductions(array &$aspectContainers, $targetClassName) {
		$introductions = array();
		foreach ($aspectContainers as $aspectContainer) {
			if (!$aspectContainer->getCachedTargetClassNameCandidates()->hasClassName($targetClassName)) {
				continue;
			}
			foreach ($aspectContainer->getInterfaceIntroductions() as $introduction) {
				$pointcut = $introduction->getPointcut();
				if ($pointcut->matches($targetClassName, NULL, NULL, uniqid())) {
					$introductions[] = $introduction;
				}
			}
		}
		return $introductions;
	}

	/**
	 * Traverses all aspect containers and returns an array of property
	 * introductions which match the target class.
	 *
	 * @param array &$aspectContainers All aspects to take into consideration
	 * @param string $targetClassName Name of the class the pointcut should match with
	 * @return array array of property introductions
	 */
	protected function getMatchingPropertyIntroductions(array &$aspectContainers, $targetClassName) {
		$introductions = array();
		foreach ($aspectContainers as $aspectContainer) {
			if (!$aspectContainer->getCachedTargetClassNameCandidates()->hasClassName($targetClassName)) {
				continue;
			}
			foreach ($aspectContainer->getPropertyIntroductions() as $introduction) {
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
	 * @param array $interfaceIntroductions An array of interface introductions
	 * @return array Array of interface names
	 */
	protected function getInterfaceNamesFromIntroductions(array $interfaceIntroductions) {
		$interfaceNames = array();
		foreach ($interfaceIntroductions as $introduction) {
			$interfaceNames[] = '\\' . $introduction->getInterfaceName();
		}
		return $interfaceNames;
	}

	/**
	 * Returns all methods declared by the introduced interfaces
	 *
	 * @param array $interfaceIntroductions An array of \TYPO3\Flow\Aop\InterfaceIntroduction
	 * @return array An array of method information (interface, method name)
	 * @throws \TYPO3\Flow\Aop\Exception
	 */
	protected function getIntroducedMethodsFromInterfaceIntroductions(array $interfaceIntroductions) {
		$methods = array();
		$methodsAndIntroductions = array();
		foreach ($interfaceIntroductions as $introduction) {
			$interfaceName = $introduction->getInterfaceName();
			$methodNames = get_class_methods($interfaceName);
			if (is_array($methodNames)) {
				foreach ($methodNames as $newMethodName) {
					if (isset($methodsAndIntroductions[$newMethodName])) {
						throw new \TYPO3\Flow\Aop\Exception('Method name conflict! Method "' . $newMethodName . '" introduced by "' . $introduction->getInterfaceName() . '" declared in aspect "' . $introduction->getDeclaringAspectClassName() . '" has already been introduced by "' . $methodsAndIntroductions[$newMethodName]->getInterfaceName() . '" declared in aspect "' . $methodsAndIntroductions[$newMethodName]->getDeclaringAspectClassName() . '".', 1173020942);
					}
					$methods[] = array($interfaceName, $newMethodName);
					$methodsAndIntroductions[$newMethodName] = $introduction;
				}
			}
		}
		return $methods;
	}

	/**
	 * Adds a "getAdviceChains()" method to the current proxy class.
	 *
	 * @param string $targetClassName
	 * @return void
	 */
	protected function buildGetAdviceChainsMethodCode($targetClassName) {
		$proxyMethod = $this->compiler->getProxyClass($targetClassName)->getMethod('Flow_Aop_Proxy_getAdviceChains');
		$proxyMethod->setMethodParametersCode('$methodName');
		$proxyMethod->overrideMethodVisibility('private');

		$code = <<<'EOT'
		$adviceChains = array();
		if (isset($this->Flow_Aop_Proxy_groupedAdviceChains[$methodName])) {
			$adviceChains = $this->Flow_Aop_Proxy_groupedAdviceChains[$methodName];
		} else {
			if (isset($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[$methodName])) {
				$groupedAdvices = $this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices[$methodName];
				if (isset($groupedAdvices['TYPO3\Flow\Aop\Advice\AroundAdvice'])) {
					$this->Flow_Aop_Proxy_groupedAdviceChains[$methodName]['TYPO3\Flow\Aop\Advice\AroundAdvice'] = new \TYPO3\Flow\Aop\Advice\AdviceChain($groupedAdvices['TYPO3\Flow\Aop\Advice\AroundAdvice']);
					$adviceChains = $this->Flow_Aop_Proxy_groupedAdviceChains[$methodName];
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
	 * @param string $targetClassName
	 * @return void
	 */
	protected function buildInvokeJoinPointMethodCode($targetClassName) {
		$proxyMethod = $this->compiler->getProxyClass($targetClassName)->getMethod('Flow_Aop_Proxy_invokeJoinPoint');
		$proxyMethod->setMethodParametersCode('\TYPO3\Flow\Aop\JoinPointInterface $joinPoint');
		$code = <<<'EOT'
		if (__CLASS__ !== $joinPoint->getClassName()) return parent::Flow_Aop_Proxy_invokeJoinPoint($joinPoint);
		if (isset($this->Flow_Aop_Proxy_methodIsInAdviceMode[$joinPoint->getMethodName()])) {
			return call_user_func_array(array('self', $joinPoint->getMethodName()), $joinPoint->getMethodArguments());
		}

EOT;
		$proxyMethod->addPreParentCallCode($code);
	}

	/**
	 * Renders a short message which gives a hint on where the currently parsed pointcut expression was defined.
	 *
	 * @param string $aspectClassName
	 * @param string $methodName
	 * @param string $tagName
	 * @return string
	 */
	protected function renderSourceHint($aspectClassName, $methodName, $tagName) {
		return sprintf('%s::%s (%s advice)', $aspectClassName, $methodName, $tagName);
	}
}
