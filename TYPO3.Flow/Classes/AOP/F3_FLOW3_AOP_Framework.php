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
 * @version $Id:F3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_AOP_Framework {

	/**
	 * @var F3_FLOW3_Component_ManagerInterface An instance of the component manager
	 */
	protected $componentManager;

	/**
	 * @var F3_FLOW3_Configuration_Container The FLOW3 configuration
	 */
	protected $configuration;

	/**
	 * @var F3_FLOW3_AOP_PointcutExpressionParserInterface An instance of the pointcut expression parser
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
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager: An instance of the component manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
		$this->registerFrameworkComponents();
		$this->configuration = $componentManager->getComponent('F3_FLOW3_Configuration_Manager')->getConfiguration('FLOW3', F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_FLOW3);
	}

	/**
	 * Injects an instance of the pointcut expression parser
	 *
	 * @param F3_FLOW3_AOP_PointcutExpressionParser $pointcutExpressionParser
	 * @return void
	 * @required
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPointcutExpressionParser(F3_FLOW3_AOP_PointcutExpressionParser $pointcutExpressionParser) {
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
	 * AOP framework. Finally all advices are woven into their target classes by generating
	 * proxy classes.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize($componentConfigurations) {
		if ($this->isInitialized) throw new F3_FLOW3_AOP_Exception('The AOP framework has already been initialized!', 1169550994);

		$loadedFromCache = FALSE;
		$context = $this->componentManager->getContext();

		if ($this->configuration->aop->proxyCache->enable) {
			$cacheBackend = $this->componentManager->getComponent($this->configuration->aop->proxyCache->backend, $context);
			$proxyCache = $this->componentManager->getComponent('F3_FLOW3_Cache_VariableCache', 'FLOW3_AOP_Proxy', $cacheBackend);
			$configurationCache = $this->componentManager->getComponent('F3_FLOW3_Cache_VariableCache', 'FLOW3_AOP_Configuration', clone $cacheBackend);

			if ($proxyCache->has('proxyBuildResults') && $configurationCache->has('advicedMethodsInformationByTargetClass')) {
				$GLOBALS['FLOW3']['Cache']['Wakeup']['F3_FLOW3_AOP_Framework'] = $this;
				$proxyBuildResults =  $proxyCache->load('proxyBuildResults');
				$this->advicedMethodsInformationByTargetClass = $configurationCache->load('advicedMethodsInformationByTargetClass');
				$aspectContainers = $configurationCache->load('aspectContainers');
				$loadedFromCache = TRUE;
			}
		}

		if (!$loadedFromCache) {
			$namesOfAvailableClasses = array();
			foreach ($componentConfigurations as $componentConfiguration) {
				$namesOfAvailableClasses[] = $componentConfiguration->getClassName();
			}
			$aspectContainers = $this->buildAspectContainersFromClasses($namesOfAvailableClasses);
			$proxyBuildResults = $this->buildProxyClasses($namesOfAvailableClasses, $aspectContainers, $context);
		}

		foreach ($proxyBuildResults as $targetClassName => $proxyBuildResult) {
			$this->targetAndProxyClassNames[$targetClassName] = $proxyBuildResult['proxyClassName'];
			if (!class_exists($proxyBuildResult['proxyClassName'])) {
				eval($proxyBuildResult['proxyClassCode']);
			}
		}

		if ($this->configuration->aop->proxyCache->enable && !$loadedFromCache) {
			$configurationCache->save('advicedMethodsInformationByTargetClass', $this->advicedMethodsInformationByTargetClass);
			$configurationCache->save('aspectContainers', $aspectContainers);
			$proxyCache->save('proxyBuildResults', $proxyBuildResults);
		}

		$this->aspectContainers = $aspectContainers;
		$this->isInitialized = TRUE;
	}

	/**
	 * If the AOP Framework has been initilaized already.
	 *
	 * @return boolean If the framework has been initialized
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isInitialized() {
		return $this->isInitialized;
	}

	/**
	 * Returns the names of all target and their proxy classes which were affected by
	 * at least one advice and therefore needed to be proxied.
	 *
	 * @return array An array of target class names and their proxy counterpart.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTargetAndProxyClassNames() {
		return $this->targetAndProxyClassNames;
	}

	/**
	 * Traverses the aspect containers to find a pointcut from the aspect class name
	 * and pointcut method name
	 *
	 * @param string $aspectClassName: Name of the aspect class where the pointcut has been declared
	 * @param string $pointcutMethodName: Method name of the pointcut
	 * @return mixed The F3_FLOW3AOPPointcut or FALSE if none was found
	 * @throws F3_FLOW3_AOP_Exception if no aspect container was defined for the given aspect class name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findPointcut($aspectClassName, $pointcutMethodName) {
		if (!$this->isInitialized) throw new F3_FLOW3_AOP_Exception('The AOP framework has not yet been initialized!', 1207216396);
		if (!isset($this->aspectContainers[$aspectClassName])) throw new F3_FLOW3_AOP_Exception('No aspect class "' . $aspectClassName . '" found while searching for pointcut "' . $pointcutMethodName . '".', 1172223654);
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
	 * @return mixed An array of method names and their advices as array of F3_FLOW3_AOP_AdviceInterface
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAdvicedMethodsInformationByTargetClass($targetClassName) {
		if (!isset($this->advicedMethodsInformationByTargetClass[$targetClassName])) return array();
		return $this->advicedMethodsInformationByTargetClass[$targetClassName];
	}

	/**
	 * Checks the annotations of the specified classes for aspect tags
	 * and creates an aspect with advisors accordingly.
	 *
	 * @param array $classNames: Classes to check for aspect tags.
	 * @return array An array of F3_FLOW3_AOP_AspectContainer for all aspects which were found.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildAspectContainersFromClasses($classNames) {
		$aspectContainers = array();
		foreach ($classNames as $className) {
			if (class_exists($className, TRUE)) {
				$class = new F3_FLOW3_Reflection_Class($className);
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
	 * @param  F3_FLOW3_Reflection_Class $aspectClass: Class which forms the aspect, contains advices etc.
	 * @return F3_FLOW3_AOP_AspectContainer The aspect container containing one or more advisors
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildAspectContainerFromClass(F3_FLOW3_Reflection_Class $aspectClass) {
		$aspectClassName = $aspectClass->getName();
		$aspectContainer = new F3_FLOW3_AOP_AspectContainer($aspectClassName);

		foreach ($aspectClass->getMethods() as $method) {
			$methodName = $method->getName();
			foreach ($method->getTagsValues() as $tagName => $tagValues) {
				foreach ($tagValues as $tagValue) {
					switch ($tagName) {
						case 'around' :
							if (!$aspectClass->implementsInterface('F3_FLOW3_AOP_AspectInterface')) throw new RuntimeException('The class "' . $aspectClassName . '" contains an around advice but does not implement the neccessary aspect interface.', 1168868680);
							$advice = $this->componentManager->getComponent('F3_FLOW3_AOP_AroundAdvice', $aspectClass->getName(), $methodName);
							$pointcut = $this->componentManager->getComponent('F3_FLOW3_AOP_Pointcut', $tagValue, $this->pointcutExpressionParser, $aspectClassName);
							$advisor = $this->componentManager->getComponent('F3_FLOW3_AOP_Advisor', $advice, $pointcut);
							$aspectContainer->addAdvisor($advisor);
						break;
						case 'before' :
							if (!$aspectClass->implementsInterface('F3_FLOW3_AOP_AspectInterface')) throw new RuntimeException('The class "' . $aspectClassName . '" contains a before advice but does not implement the neccessary aspect interface.', 1169035119);
							$advice = $this->componentManager->getComponent('F3_FLOW3_AOP_BeforeAdvice', $aspectClass->getName(), $methodName);
							$pointcut = $this->componentManager->getComponent('F3_FLOW3_AOP_Pointcut', $tagValue, $this->pointcutExpressionParser, $aspectClassName);
							$advisor = $this->componentManager->getComponent('F3_FLOW3_AOP_Advisor', $advice, $pointcut);
							$aspectContainer->addAdvisor($advisor);
						break;
						case 'afterreturning' :
							if (!$aspectClass->implementsInterface('F3_FLOW3_AOP_AspectInterface')) throw new RuntimeException('The class "' . $aspectClassName . '" icontains an after returning advice but does not implement the neccessary aspect interface.', 1171484136);
							$advice = $this->componentManager->getComponent('F3_FLOW3_AOP_AfterReturningAdvice', $aspectClass->getName(), $methodName);
							$pointcut = $this->componentManager->getComponent('F3_FLOW3_AOP_Pointcut', $tagValue, $this->pointcutExpressionParser, $aspectClassName);
							$advisor = $this->componentManager->getComponent('F3_FLOW3_AOP_Advisor', $advice, $pointcut);
							$aspectContainer->addAdvisor($advisor);
						break;
						case 'afterthrowing' :
							if (!$aspectClass->implementsInterface('F3_FLOW3_AOP_AspectInterface')) throw new RuntimeException('The class "' . $aspectClassName . '" contains an after throwing advice but does not implement the neccessary aspect interface.', 1171551836);
							$advice = $this->componentManager->getComponent('F3_FLOW3_AOP_AfterThrowingAdvice', $aspectClass->getName(), $methodName);
							$pointcut = $this->componentManager->getComponent('F3_FLOW3_AOP_Pointcut', $tagValue, $this->pointcutExpressionParser, $aspectClassName);
							$advisor = $this->componentManager->getComponent('F3_FLOW3_AOP_Advisor', $advice, $pointcut);
							$aspectContainer->addAdvisor($advisor);
						break;
						case 'pointcut' :
							if (!$aspectClass->implementsInterface('F3_FLOW3_AOP_AspectInterface')) throw new RuntimeException('The class "' . $aspectClassName . '" contains a pointcut declaration but does not implement the neccessary aspect interface.', 1172158809);
							$pointcut = $this->componentManager->getComponent('F3_FLOW3_AOP_Pointcut', $tagValue, $this->pointcutExpressionParser, $aspectClassName, $methodName);
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
							if (!$aspectClass->implementsInterface('F3_FLOW3_AOP_AspectInterface')) throw new RuntimeException('The class "' . $aspectClassName . '" contains an introduction declaration but does not implement the neccessary aspect interface.', 1172694758);
							$splittedTagValue = explode(',', $tagValue);
							if (!is_array($splittedTagValue) || count($splittedTagValue) != 2)  throw new RuntimeException('The introduction in class "' . $aspectClassName . '" does not contain the two required parameters.', 1172694761);
							$pointcut = $this->componentManager->getComponent('F3_FLOW3_AOP_Pointcut', trim($splittedTagValue[1]), $this->pointcutExpressionParser, $aspectClassName);
							$introduction = $this->componentManager->getComponent('F3_FLOW3_AOP_Introduction', $aspectClassName, trim($splittedTagValue[0]), $pointcut);
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
	 * @param array $classNames Class names to take into consideration.
	 * @param array $aspectContainers Aspect containers whose pointcuts are matched against the specified classes.
	 * @param string $context The application context to build proxy classes for.
	 * @return array $proxyBuildResults An array which contains at least the proxy class name and the generated code, indexed by the target class names
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildProxyClasses(array $classNames, array $aspectContainers, $context) {
		$proxyBuildResults = array();

		foreach ($classNames as $targetClassName) {
			if (array_search($targetClassName, $this->componentProxyBlacklist) === FALSE && substr($targetClassName, 0, 13) != 'F3_FLOW3_') {
				try {
					$class = new F3_FLOW3_Reflection_Class($targetClassName);
					if (!$class->implementsInterface('F3_FLOW3_AOP_AspectInterface') && !$class->isAbstract() && !$class->isFinal()) {
						$proxyBuildResult = F3_FLOW3_AOP_ProxyClassBuilder::buildProxyClass($class, $aspectContainers, $context);
						if ($proxyBuildResult !== FALSE) {
							$proxyBuildResults[$targetClassName] = $proxyBuildResult;
							$this->advicedMethodsInformationByTargetClass[$targetClassName] = $proxyBuildResult['advicedMethodsInformation'];
						}
					}
				} catch (ReflectionException $exception) {
					throw new F3_FLOW3_AOP_Exception_UnknownClass('The class "' . $targetClassName . '" does not exist.', 1187348208);
				}
			}
		}
		return $proxyBuildResults;
	}

	/**
	 * Registers certain classes of the AOP Framework as components, so they can
	 * be used for dependency injection elsewhere.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function registerFrameworkComponents() {
		$this->componentManager->registerComponent('F3_FLOW3_AOP_Advisor');
		$this->componentManager->registerComponent('F3_FLOW3_AOP_AfterReturningAdvice');
		$this->componentManager->registerComponent('F3_FLOW3_AOP_AfterThrowingAdvice');
		$this->componentManager->registerComponent('F3_FLOW3_AOP_AroundAdvice');
		$this->componentManager->registerComponent('F3_FLOW3_AOP_BeforeAdvice');
		$this->componentManager->registerComponent('F3_FLOW3_AOP_Introduction');
		$this->componentManager->registerComponent('F3_FLOW3_AOP_Pointcut');
		$this->componentManager->registerComponent('F3_FLOW3_AOP_PointcutInterface', 'F3_FLOW3_AOP_Pointcut');
		$this->componentManager->registerComponent('F3_FLOW3_AOP_PointcutFilter');
		$this->componentManager->registerComponent('F3_FLOW3_AOP_PointcutExpressionParser');

		$componentConfigurations = $this->componentManager->getComponentConfigurations();
		$componentConfigurations['F3_FLOW3_AOP_Advisor']->setScope('prototype');
		$componentConfigurations['F3_FLOW3_AOP_AfterReturningAdvice']->setScope('prototype');
		$componentConfigurations['F3_FLOW3_AOP_AfterThrowingAdvice']->setScope('prototype');
		$componentConfigurations['F3_FLOW3_AOP_AroundAdvice']->setScope('prototype');
		$componentConfigurations['F3_FLOW3_AOP_BeforeAdvice']->setScope('prototype');
		$componentConfigurations['F3_FLOW3_AOP_Introduction']->setScope('prototype');
		$componentConfigurations['F3_FLOW3_AOP_Introduction']->setAutowiringMode(F3_FLOW3_Component_Configuration::AUTOWIRING_MODE_OFF);
		$componentConfigurations['F3_FLOW3_AOP_Pointcut']->setScope('prototype');
		$componentConfigurations['F3_FLOW3_AOP_PointcutInterface']->setScope('prototype');
		$componentConfigurations['F3_FLOW3_AOP_PointcutFilter']->setScope('prototype');
		$this->componentManager->setComponentConfigurations($componentConfigurations);
	}
}
?>