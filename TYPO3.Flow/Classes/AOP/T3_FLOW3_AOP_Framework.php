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
 * The main class of the AOP (Aspect Oriented Programming) framework.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:T3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_AOP_Framework {

	/**
	 * @var T3_FLOW3_Component_ManagerInterface An instance of the component manager
	 */
	protected $componentManager;

	/**
	 * @var T3_FLOW3_AOP_PointcutExpressionParserInterface An instance of the pointcut expression parser
	 */
	protected $pointcutExpressionParser;

	/**
	 * @var array A registry of all known aspects
	 */
	protected $aspectContainers = array();

	/**
	 * @var array An array of all proxied class names and adviced methods with information about the advice which has been applied.
	 */
	protected $advicedMethodsInformationByTargetClass = array();

	/**
	 * @var array An array target class names and their proxy class name.
	 */
	protected $targetAndProxyClassNames = array();

	/**
	 * @var T3_FLOW3_Cache_VariableCache The cache for AOP proxy classes
	 */
	protected $proxyCache;

	/**
	 * @var T3_FLOW3_Cache_VariableCache The cache for AOP configuration
	 */
	protected $configurationCache;

	/**
	 * @var array List of component names which must not be proxied. The blacklist must be complete before calling initialize()!
	 */
	protected $componentProxyBlacklist = array();

	/**
	 * @var boolean Flag which signals if this class has already been initialized.
	 */
	protected $isInitialized = FALSE;

	/**
	 * Constructor
	 *
	 * @param T3_FLOW3_Component_ManagerInterface $componentManager: An instance of the component manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(T3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
		$this->registerFrameworkComponents();
	}

	/**
	 * Injects an instance of the pointcut expression parser
	 *
	 * @param T3_FLOW3_AOP_PointcutExpressionParser $pointcutExpressionParser
	 * @return void
	 * @required
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPointcutExpressionParser(T3_FLOW3_AOP_PointcutExpressionParser $pointcutExpressionParser) {
		$this->pointcutExpressionParser = $pointcutExpressionParser;
	}

	/**
	 * Adds a registered component to the proxy blacklist to prevent the component class
	 * from being proxied by the AOP framework.
	 *
	 * @param string $componentName: Name of the component to add to the blacklist
	 * @return void
	 * @auhor Robert Lemke <robert@typo3.org>
	 */
	public function addComponentNameToProxyBlacklist($componentName) {
		if ($this->isInitialized) throw new RuntimeException('Cannot add components to the proxy blacklist after the AOP framework has been initialized!', 1169550998);
		$this->componentProxyBlacklist[$componentName] = $componentName;
	}

	/**
	 * Initializes the AOP framework.
	 *
	 * During initialization the configuration of all registered components is searched for
	 * possible aspect annotations. If an aspect class is found, the poincut expressions are
	 * parsed and a new aspect with one or more advisors is added to the aspect registry of the
	 * AOP framework.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize() {
		if ($this->isInitialized) throw new T3_FLOW3_AOP_Exception('The AOP framework has already been initialized!', 1169550994);

		$context = $this->componentManager->getContext();
		$cacheBackend = $this->componentManager->getComponent('T3_FLOW3_Cache_Backend_File', $context);
		$this->proxyCache = $this->componentManager->getComponent('T3_FLOW3_Cache_VariableCache', 'FLOW3_AOP_Proxy', $cacheBackend);
		$this->configurationCache = $this->componentManager->getComponent('T3_FLOW3_Cache_VariableCache', 'FLOW3_AOP_Configuration', clone $cacheBackend);

		$componentConfigurations = $this->componentManager->getComponentConfigurations();
		if ($context != 'Testing' && $this->configurationCache->has('targetAndProxyClassNames') && $this->configurationCache->has('advicedMethodsInformationByTargetClass')) {
			$aspectContainers = $this->configurationCache->load('aspectContainers');
			$this->targetAndProxyClassNames = $this->configurationCache->load('targetAndProxyClassNames');
			$this->advicedMethodsInformationByTargetClass = $this->configurationCache->load('advicedMethodsInformationByTargetClass');
		} else {
			$namesOfAvailableClasses = array();
			foreach ($componentConfigurations as $componentConfiguration) {
				$namesOfAvailableClasses[] = $componentConfiguration->getClassName();
			}

			$aspectContainers = $this->buildAspectContainersFromClasses($namesOfAvailableClasses);
			$this->targetAndProxyClassNames = $this->buildProxyClasses($namesOfAvailableClasses, $aspectContainers, $context);
			$this->configurationCache->save('targetAndProxyClassNames', $this->targetAndProxyClassNames);
			$this->configurationCache->save('advicedMethodsInformationByTargetClass', $this->advicedMethodsInformationByTargetClass);
		}

		foreach ($this->targetAndProxyClassNames as $targetClassName => $proxyClassName) {
			$componentConfigurations[$targetClassName]->setClassName($proxyClassName);
		}
		$this->componentManager->setComponentConfigurations($componentConfigurations);
		$this->aspectContainers = $aspectContainers;
		$this->isInitialized = TRUE;
	}

	/**
	 * Traverses the aspect containers to find a pointcut from the aspect class name
	 * and pointcut method name
	 *
	 * @param string $aspectClassName: Name of the aspect class where the pointcut has been declared
	 * @param string $pointcutMethodName: Method name of the pointcut
	 * @return mixed The T3_FLOW3AOPPointcut or FALSE if none was found
	 * @throws T3_FLOW3_AOP_Exception if no aspect container was defined for the given aspect class name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findPointcut($aspectClassName, $pointcutMethodName) {
		if (!isset($this->aspectContainers[$aspectClassName])) throw new T3_FLOW3_AOP_Exception('No aspect class "' . $aspectClassName . '" found while searching for pointcut "' . $pointcutMethodName . '".', 1172223654);
		foreach ($this->aspectContainers[$aspectClassName]->getPointcuts() as $pointcut) {
			if ($pointcut->getPointcutMethodName() == $pointcutMethodName) {
				return $pointcut;
			}
		}
		return FALSE;
	}

	/**
	 * Returns an array of method names and advices which were applied to the specified class. If the
	 * target class has no adviced methods, an empty array is returned.
	 *
	 * @param string $targetClassName: Name of the target class
	 * @return mixed An array of method names and their advices as array of T3_FLOW3_AOP_AdviceInterface
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAdvicedMethodsInformationByTargetClass($targetClassName) {
		if (!isset($this->advicedMethodsInformationByTargetClass[$targetClassName])) return array();
		return $this->advicedMethodsInformationByTargetClass[$targetClassName];
	}

	/**
	 * Returns an array of all target class names which have been proxied and the name
	 * of the respective proxy class.
	 *
	 * @return array target class names and their proxy class name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTargetAndProxyClassNames() {
		return $this->targetAndProxyClassNames;
	}

	/**
	 * Checks the annotations of the specified classes for aspect tags
	 * and creates an aspect with advisors accordingly.
	 *
	 * @param array $classNames: Classes to check for aspect tags.
	 * @return array An array of T3_FLOW3_AOP_AspectContainer for all aspects which were found.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildAspectContainersFromClasses($classNames) {
		$aspectContainers = array();
		foreach ($classNames as $className) {
			if (class_exists($className, TRUE)) {
				$class = new T3_FLOW3_Reflection_Class($className);
				if ($class->isTaggedWith('aspect')) {
					$aspectContainer =  $this->buildAspectContainerFromClass($class);
					if ($aspectContainer !== NULL) {
						$aspectContainers[$class->getName()] = $aspectContainer;
					}
				}
			}
		}
		return $aspectContainers;
	}

	/**
	 * Creates and returns an aspect from the annotations found in a class which
	 * is tagged as an aspect. The component acting as an advice will already be
	 * fetched (and therefore instantiated if neccessary).
	 *
	 * @param  T3_FLOW3_Reflection_Class $aspectClass: Class which forms the aspect, contains advices etc.
	 * @return T3_FLOW3_AOP_AspectContainer The aspect container containing one or more advisors
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildAspectContainerFromClass(T3_FLOW3_Reflection_Class $aspectClass) {
		$aspectClassName = $aspectClass->getName();
		$aspectContainer = new T3_FLOW3_AOP_AspectContainer($aspectClassName);

		foreach ($aspectClass->getMethods() as $method) {
			$methodName = $method->getName();
			foreach ($method->getTagsValues() as $tagName => $tagValues) {
				foreach ($tagValues as $tagValue) {
					switch ($tagName) {
						case 'around' :
							if (!$aspectClass->implementsInterface('T3_FLOW3_AOP_AspectInterface')) throw new RuntimeException('The class "' . $aspectClassName . '" contains an around advice but does not implement the neccessary aspect interface.', 1168868680);
							$advice = $this->componentManager->getComponent('T3_FLOW3_AOP_AroundAdvice', $aspectClass->getName(), $methodName);
							$pointcut = $this->componentManager->getComponent('T3_FLOW3_AOP_Pointcut', $tagValue, $this->pointcutExpressionParser, $aspectClassName);
							$advisor = $this->componentManager->getComponent('T3_FLOW3_AOP_Advisor', $advice, $pointcut);
							$aspectContainer->addAdvisor($advisor);
						break;
						case 'before' :
							if (!$aspectClass->implementsInterface('T3_FLOW3_AOP_AspectInterface')) throw new RuntimeException('The class "' . $aspectClassName . '" contains a before advice but does not implement the neccessary aspect interface.', 1169035119);
							$advice = $this->componentManager->getComponent('T3_FLOW3_AOP_BeforeAdvice', $aspectClass->getName(), $methodName);
							$pointcut = $this->componentManager->getComponent('T3_FLOW3_AOP_Pointcut', $tagValue, $this->pointcutExpressionParser, $aspectClassName);
							$advisor = $this->componentManager->getComponent('T3_FLOW3_AOP_Advisor', $advice, $pointcut);
							$aspectContainer->addAdvisor($advisor);
						break;
						case 'afterreturning' :
							if (!$aspectClass->implementsInterface('T3_FLOW3_AOP_AspectInterface')) throw new RuntimeException('The class "' . $aspectClassName . '" icontains an after returning advice but does not implement the neccessary aspect interface.', 1171484136);
							$advice = $this->componentManager->getComponent('T3_FLOW3_AOP_AfterReturningAdvice', $aspectClass->getName(), $methodName);
							$pointcut = $this->componentManager->getComponent('T3_FLOW3_AOP_Pointcut', $tagValue, $this->pointcutExpressionParser, $aspectClassName);
							$advisor = $this->componentManager->getComponent('T3_FLOW3_AOP_Advisor', $advice, $pointcut);
							$aspectContainer->addAdvisor($advisor);
						break;
						case 'afterthrowing' :
							if (!$aspectClass->implementsInterface('T3_FLOW3_AOP_AspectInterface')) throw new RuntimeException('The class "' . $aspectClassName . '" contains an after throwing advice but does not implement the neccessary aspect interface.', 1171551836);
							$advice = $this->componentManager->getComponent('T3_FLOW3_AOP_AfterThrowingAdvice', $aspectClass->getName(), $methodName);
							$pointcut = $this->componentManager->getComponent('T3_FLOW3_AOP_Pointcut', $tagValue, $this->pointcutExpressionParser, $aspectClassName);
							$advisor = $this->componentManager->getComponent('T3_FLOW3_AOP_Advisor', $advice, $pointcut);
							$aspectContainer->addAdvisor($advisor);
						break;
						case 'pointcut' :
							if (!$aspectClass->implementsInterface('T3_FLOW3_AOP_AspectInterface')) throw new RuntimeException('The class "' . $aspectClassName . '" contains a pointcut declaration but does not implement the neccessary aspect interface.', 1172158809);
							$pointcut = $this->componentManager->getComponent('T3_FLOW3_AOP_Pointcut', $tagValue, $this->pointcutExpressionParser, $aspectClassName, $methodName);
							$aspectContainer->addPointcut($pointcut);
						break;
					}
				}
			}
		}

		foreach ($aspectClass->getProperties() as $property) {
			$propertyName = $property->getName();
			foreach ($property->getTagsValues() as $tagName => $tagValues) {
				foreach ($tagValues as $tagValue) {
					switch ($tagName) {
						case 'introduce' :
							if (!$aspectClass->implementsInterface('T3_FLOW3_AOP_AspectInterface')) throw new RuntimeException('The class "' . $aspectClassName . '" contains an introduction declaration but does not implement the neccessary aspect interface.', 1172694758);
							$splittedTagValue = explode(',', $tagValue);
							if (!is_array($splittedTagValue) || count($splittedTagValue) != 2)  throw new RuntimeException('The introduction in class "' . $aspectClassName . '" does not contain the two required parameters.', 1172694761);
							$pointcut = $this->componentManager->getComponent('T3_FLOW3_AOP_Pointcut', trim($splittedTagValue[1]), $this->pointcutExpressionParser, $aspectClassName);
							$introduction = $this->componentManager->getComponent('T3_FLOW3_AOP_Introduction', $aspectClassName, trim($splittedTagValue[0]), $pointcut);
							$aspectContainer->addIntroduction($introduction);
						break;
					}
				}
			}
		}

		if (count($aspectContainer->getAdvisors()) < 1 && count($aspectContainer->getPointcuts()) < 1 && count($aspectContainer->getIntroductions()) < 1) throw new RuntimeException('The class "' . $aspectClass->getName() . '" is tagged to be an aspect but doesn\'t contain advices nor pointcut or introduction declarations.', 1169124534);
		return $aspectContainer;
	}

	/**
	 * Tests for all specified classes if they match one or more pointcuts and if so
	 * builds an AOP proxy class which contains interceptor code. Returns all class
	 * names (and the name of their new proxy class) of those classes which needed to
	 * be proxied.
	 *
	 * @param array $classNames: Class names to take into consideration.
	 * @param array $aspectContainers: Aspect containers whose pointcuts are matched against the specified classes.
	 * @param string $context: The application context to build proxy classes for.
	 * @return array $targetAndProxyClassNames The names of target classes which have been proxied - and the name of the respective proxy class.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildProxyClasses(array $classNames, array $aspectContainers, $context) {
		$targetAndProxyClassNames = array();

		foreach ($classNames as $targetClassName) {
			if (array_search($targetClassName, $this->componentProxyBlacklist) === FALSE && substr($targetClassName, 0, 13) != 'T3_FLOW3_') {
				try {
					$class = new T3_FLOW3_Reflection_Class($targetClassName);
					if (!$class->implementsInterface('T3_FLOW3_AOP_AspectInterface') && !$class->isAbstract() && !$class->isFinal()) {
						$proxyBuildResult = T3_FLOW3_AOP_ProxyClassBuilder::buildProxyClass($class, $aspectContainers, $context);
						if ($proxyBuildResult !== FALSE) {
							eval($proxyBuildResult['proxyClassCode']);
							if ($this->proxyCache !== NULL) {
								$this->proxyCache->save($proxyBuildResult['proxyClassName'], $proxyBuildResult['proxyClassCode']);
							}
							$targetAndProxyClassNames[$targetClassName] = $proxyBuildResult['proxyClassName'];
							$this->advicedMethodsInformationByTargetClass[$targetClassName] = $proxyBuildResult['advicedMethodsInformation'];
						}
					}
				} catch (ReflectionException $exception) {
					throw new T3_FLOW3_AOP_Exception_UnknownClass('The class "' . $targetClassName . '" does not exist.', 1187348208);
				}
			}
		}
		return $targetAndProxyClassNames;
	}

	/**
	 * Registers certain classes of the AOP Framework as components, so they can
	 * be used for dependency injection elsewhere.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function registerFrameworkComponents() {
		$this->componentManager->registerComponent('T3_FLOW3_AOP_Advisor');
		$this->componentManager->registerComponent('T3_FLOW3_AOP_AfterReturningAdvice');
		$this->componentManager->registerComponent('T3_FLOW3_AOP_AfterThrowingAdvice');
		$this->componentManager->registerComponent('T3_FLOW3_AOP_AroundAdvice');
		$this->componentManager->registerComponent('T3_FLOW3_AOP_BeforeAdvice');
		$this->componentManager->registerComponent('T3_FLOW3_AOP_Introduction');
		$this->componentManager->registerComponent('T3_FLOW3_AOP_Pointcut');
		$this->componentManager->registerComponent('T3_FLOW3_AOP_PointcutInterface', 'T3_FLOW3_AOP_Pointcut');
		$this->componentManager->registerComponent('T3_FLOW3_AOP_PointcutFilter');
		$this->componentManager->registerComponent('T3_FLOW3_AOP_PointcutExpressionParser');

		$componentConfigurations = $this->componentManager->getComponentConfigurations();
		$componentConfigurations['T3_FLOW3_AOP_Advisor']->setScope('prototype');
		$componentConfigurations['T3_FLOW3_AOP_AfterReturningAdvice']->setScope('prototype');
		$componentConfigurations['T3_FLOW3_AOP_AfterThrowingAdvice']->setScope('prototype');
		$componentConfigurations['T3_FLOW3_AOP_AroundAdvice']->setScope('prototype');
		$componentConfigurations['T3_FLOW3_AOP_BeforeAdvice']->setScope('prototype');
		$componentConfigurations['T3_FLOW3_AOP_Introduction']->setScope('prototype');
		$componentConfigurations['T3_FLOW3_AOP_Introduction']->setAutowiringMode(T3_FLOW3_Component_Configuration::AUTOWIRING_MODE_OFF);
		$componentConfigurations['T3_FLOW3_AOP_Pointcut']->setScope('prototype');
		$componentConfigurations['T3_FLOW3_AOP_PointcutInterface']->setScope('prototype');
		$componentConfigurations['T3_FLOW3_AOP_PointcutFilter']->setScope('prototype');
		$this->componentManager->setComponentConfigurations($componentConfigurations);
	}
}
?>