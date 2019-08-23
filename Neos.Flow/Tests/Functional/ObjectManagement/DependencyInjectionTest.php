<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\FinalClassWithDependencies;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\Flow175\ClassWithTransitivePrototypeDependency;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassA;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the Dependency Injection features
 *
 */
class DependencyInjectionTest extends FunctionalTestCase
{
    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurationManager = $this->objectManager->get(ConfigurationManager::class);
    }

    /**
     * @test
     */
    public function singletonObjectsCanBeInjectedIntoConstructorsOfSingletonObjects()
    {
        $objectA = $this->objectManager->get(Fixtures\SingletonClassA::class);
        $objectB = $this->objectManager->get(Fixtures\SingletonClassB::class);

        self::assertSame($objectB, $objectA->getObjectB());
    }

    /**
     * @test
     */
    public function constructorInjectionCanHandleCombinationsOfRequiredAutowiredAndOptionalArguments()
    {
        $objectC = $this->objectManager->get(Fixtures\SingletonClassC::class);

        // Note: The "requiredArgument" and "thirdOptionalArgument" are defined in the Objects.yaml of the Flow package (testing context)
        self::assertSame('this is required', $objectC->requiredArgument);
        self::assertEquals(['thisIs' => ['anArray' => 'asProperty']], $objectC->thirdOptionalArgument);
    }

    /**
     * @test
     */
    public function propertiesOfVariousPrimitiveTypeAreSetInSingletonPropertiesIfConfigured()
    {
        $objectC = $this->objectManager->get(Fixtures\SingletonClassC::class);

        // Note: The arguments are defined in the Objects.yaml of the Flow package (testing context)
        self::assertSame('a defined string', $objectC->getProtectedStringPropertySetViaObjectsYaml());
        self::assertSame(42.101010, $objectC->getProtectedFloatPropertySetViaObjectsYaml());
        self::assertSame(['iAm' => ['aConfigured' => 'arrayValue']], $objectC->getProtectedArrayPropertySetViaObjectsYaml());
        self::assertTrue($objectC->getProtectedBooleanTruePropertySetViaObjectsYaml());
        self::assertFalse($objectC->getProtectedBooleanFalsePropertySetViaObjectsYaml());
    }

    /**
     * @test
     */
    public function ifItExistsASetterIsUsedToInjectPrimitiveTypePropertiesFromConfiguration()
    {
        $objectC = $this->objectManager->get(Fixtures\SingletonClassC::class);

        // Note: The argument is defined in the Objects.yaml of the Flow package (testing context)
        self::assertSame(['has' => 'some default value', 'and' => 'something from Objects.yaml'], $objectC->getProtectedArrayPropertyWithSetterSetViaObjectsYaml());
    }

    /**
     * @test
     */
    public function propertiesAreReinjectedIfTheObjectIsUnserialized()
    {
        $className = Fixtures\PrototypeClassA::class;

        $singletonA = $this->objectManager->get(Fixtures\SingletonClassA::class);

        $prototypeA = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
        self::assertSame($singletonA, $prototypeA->getSingletonA());
    }

    /**
     * @test
     */
    public function virtualObjectsDefinedInObjectsYamlCanUseAFactoryForTheirActualImplementation()
    {
        $prototypeA = $this->objectManager->get(Fixtures\PrototypeClassAishInterface::class);

        self::assertInstanceOf(Fixtures\PrototypeClassA::class, $prototypeA);
        self::assertSame('value defined in Objects.yaml', $prototypeA->getSomeProperty());
    }

    /**
     * @test
     */
    public function constructorInjectionInSingletonCanHandleArgumentDefinedInSettings()
    {
        $objectC = $this->objectManager->get(Fixtures\SingletonClassC::class);

        // Note: The "settingsArgument" is defined in the Settings.yaml of the Flow package (testing context)
        self::assertSame('setting injected singleton value', $objectC->settingsArgument);
    }

    /**
     * @test
     */
    public function singletonCanHandleInjectedPrototypeWithSettingArgument()
    {
        $objectD = $this->objectManager->get(Fixtures\SingletonClassD::class);

        // Note: The "settingsArgument" is defined in the Settings.yaml of the Flow package (testing context)
        self::assertSame('setting injected property value', $objectD->prototypeClassC->settingsArgument);
    }

    /**
     * @test
     */
    public function singletonCanHandleInjectedPrototypeWithCustomFactory()
    {
        $objectD = $this->objectManager->get(Fixtures\SingletonClassD::class);

        // Note: The "prototypeClassA" is defined with a custom factory in the Objects.yaml of the Flow package (testing context)
        self::assertNotNull($objectD->prototypeClassA);
        self::assertSame('value defined in Objects.yaml', $objectD->prototypeClassA->getSomeProperty());
    }

    /**
     * @test
     */
    public function singletonCanHandleConstructorArgumentWithCustomFactory()
    {
        $objectG = $this->objectManager->get(Fixtures\SingletonClassG::class);

        // Note: The "prototypeClassA" is defined with a custom factory in the Objects.yaml of the Flow package (testing context)
        self::assertNotNull($objectG->prototypeA);
        self::assertSame('Constructor injection with factory', $objectG->prototypeA->getSomeProperty());
    }

    /**
     * @test
     */
    public function onCreationOfObjectInjectionInParentClassIsDoneOnlyOnce()
    {
        $prototypeDsub = $this->objectManager->get(Fixtures\PrototypeClassDsub::class);
        self::assertSame(1, $prototypeDsub->injectionRuns);
    }

    /**
     * See http://forge.typo3.org/issues/43659
     *
     * @test
     */
    public function injectedPropertiesAreAvailableInInitializeObjectEvenIfTheClassHasBeenExtended()
    {
        $prototypeDsub = $this->objectManager->get(Fixtures\PrototypeClassDsub::class);
        self::assertFalse($prototypeDsub->injectedPropertyWasUnavailable);
    }

    /**
     * @test
     */
    public function constructorsOfSingletonObjectsAcceptNullArguments()
    {
        $objectF = $this->objectManager->get(Fixtures\SingletonClassF::class);

        self::assertNull($objectF->getNullValue());
    }

    /**
     * @test
     */
    public function constructorsOfPrototypeObjectsAcceptNullArguments()
    {
        $objectE = $this->objectManager->get(Fixtures\PrototypeClassE::class, null);

        self::assertNull($objectE->getNullValue());
    }

    /**
     * @test
     */
    public function injectionOfObjectFromSameNamespace()
    {
        $nonNamespacedDependencies = new Fixtures\ClassWithNonNamespacedDependencies();
        $classB = $this->objectManager->get(Fixtures\SingletonClassB::class);
        self::assertSame($classB, $nonNamespacedDependencies->getSingletonClassB());
    }

    /**
     * @test
     */
    public function injectionOfObjectFromSubNamespace()
    {
        $nonNamespacedDependencies = new Fixtures\ClassWithNonNamespacedDependencies();
        $aClassFromSubNamespace = $this->objectManager->get(Fixtures\SubNamespace\AnotherClass::class);
        self::assertSame($aClassFromSubNamespace, $nonNamespacedDependencies->getClassFromSubNamespace());
    }

    /**
     * @test
     */
    public function injectionOfAllSettings()
    {
        $classWithInjectedConfiguration = new Fixtures\ClassWithInjectedConfiguration();
        $actualSettings = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow');
        self::assertSame($actualSettings, $classWithInjectedConfiguration->getSettings());
    }


    /**
     * @test
     */
    public function injectionOfSpecifiedPackageSettings()
    {
        $classWithInjectedConfiguration = new Fixtures\ClassWithInjectedConfiguration();

        $actualSettings = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow');
        self::assertSame($actualSettings, $classWithInjectedConfiguration->getInjectedSpecifiedPackageSettings());
    }

    /**
     * @test
     */
    public function injectionOfCurrentPackageSettings()
    {
        $classWithInjectedConfiguration = new Fixtures\ClassWithInjectedConfiguration();

        $actualSettings = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow');
        self::assertSame($actualSettings, $classWithInjectedConfiguration->getInjectedCurrentPackageSettings());
    }

    /**
     * @test
     */
    public function injectionOfNonExistingSettingsOverridesDefaultValue()
    {
        $classWithInjectedConfiguration = new Fixtures\ClassWithInjectedConfiguration();
        self::assertNull($classWithInjectedConfiguration->getNonExistingSetting());
    }

    /**
     * @test
     */
    public function injectionOfSingleSettings()
    {
        $classWithInjectedConfiguration = new Fixtures\ClassWithInjectedConfiguration();
        self::assertSame('injected setting', $classWithInjectedConfiguration->getInjectedSettingA());
    }

    /**
     * @test
     */
    public function injectionOfSingleSettingsFromSpecificPackage()
    {
        $classWithInjectedConfiguration = new Fixtures\ClassWithInjectedConfiguration();
        self::assertSame('injected setting', $classWithInjectedConfiguration->getInjectedSettingB());
    }

    /**
     * @test
     */
    public function injectionOfConfigurationCallsRespectiveSetterIfItExists()
    {
        $classWithInjectedConfiguration = new Fixtures\ClassWithInjectedConfiguration();
        self::assertSame('INJECTED SETTING', $classWithInjectedConfiguration->getInjectedSettingWithSetter());
    }

    /**
     * @test
     */
    public function injectionOfOtherConfigurationTypes()
    {
        $classWithInjectedConfiguration = new Fixtures\ClassWithInjectedConfiguration();
        self::assertSame($this->configurationManager->getConfiguration('Views'), $classWithInjectedConfiguration->getInjectedViewsConfiguration());
    }

    /**
     * This test verifies the behaviour described in FLOW-175.
     *
     * Please note that this issue occurs ONLY when creating an object
     * with a dependency that itself takes an prototype-scoped object as
     * constructor argument and that dependency was explicitly configured
     * in the package's Objects.yaml.
     *
     * @test
     * @see https://jira.neos.io/browse/FLOW-175
     */
    public function transitivePrototypeDependenciesWithExplicitObjectConfigurationAreConstructedCorrectly()
    {
        $classWithTransitivePrototypeDependency = new ClassWithTransitivePrototypeDependency();
        self::assertEquals('Hello World!', $classWithTransitivePrototypeDependency->getTestValue());
    }

    /**
     * @test
     */
    public function dependencyInjectionWorksForFinalClasses()
    {
        $object = $this->objectManager->get(FinalClassWithDependencies::class);
        self::assertInstanceOf(SingletonClassA::class, $object->dependency);
    }
}
