<?php

declare(strict_types=1);

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
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassWithInjectedCache;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassWithInjectedConfiguration;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassWithNonNamespacedDependencies;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\FinalClassWithDependencies;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\Flow175\ClassWithTransitivePrototypeDependency;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP81\ClassWithNonPromotedReadonlyProperties;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassA;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassAishInterface;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassDsub;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassE;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassH;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassL;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassA;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassB;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassC;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassD;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassF;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassG;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SubNamespace\AnotherClass;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ValueObjectClassA;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ValueObjectClassB;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Functional tests for the Dependency Injection features
 */
final class DependencyInjectionTest extends FunctionalTestCase
{
    protected ConfigurationManager $configurationManager;
    protected CacheManager $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurationManager = $this->objectManager->get(ConfigurationManager::class);
        $this->cacheManager = $this->objectManager->get(CacheManager::class);
    }

    #[Test]
    public function singletonObjectsCanBeInjectedIntoConstructorsOfSingletonObjects(): void
    {
        $objectA = $this->objectManager->get(SingletonClassA::class);
        $objectB = $this->objectManager->get(SingletonClassB::class);

        self::assertSame($objectB, $objectA->getObjectB());
    }

    #[Test]
    public function constructorInjectionCanHandleCombinationsOfRequiredAutowiredAndOptionalArguments(): void
    {
        $objectC = $this->objectManager->get(SingletonClassC::class);

        // Note: The "requiredArgument" and "thirdOptionalArgument" are defined in the Objects.yaml of the Flow package (testing context)
        self::assertSame('this is required', $objectC->requiredArgument);
        self::assertEquals(['thisIs' => ['anArray' => 'asProperty']], $objectC->thirdOptionalArgument);
    }

    #[Test]
    public function propertiesOfVariousPrimitiveTypeAreSetInSingletonPropertiesIfConfigured(): void
    {
        $objectC = $this->objectManager->get(SingletonClassC::class);

        // Note: The arguments are defined in the Objects.yaml of the Flow package (testing context)
        self::assertSame('a defined string', $objectC->getProtectedStringPropertySetViaObjectsYaml());
        self::assertSame(42.101010, $objectC->getProtectedFloatPropertySetViaObjectsYaml());
        self::assertSame(['iAm' => ['aConfigured' => 'arrayValue']], $objectC->getProtectedArrayPropertySetViaObjectsYaml());
        self::assertTrue($objectC->getProtectedBooleanTruePropertySetViaObjectsYaml());
        self::assertFalse($objectC->getProtectedBooleanFalsePropertySetViaObjectsYaml());
        self::assertNull($objectC->getProtectedNullPropertySetViaObjectsYaml());
    }

    #[Test]
    public function ifItExistsASetterIsUsedToInjectPrimitiveTypePropertiesFromConfiguration(): void
    {
        $objectC = $this->objectManager->get(SingletonClassC::class);

        // Note: The argument is defined in the Objects.yaml of the Flow package (testing context)
        self::assertSame(['has' => 'some default value', 'and' => 'something from Objects.yaml'], $objectC->getProtectedArrayPropertyWithSetterSetViaObjectsYaml());
    }

    #[Test]
    public function propertiesAreReinjectedIfTheObjectIsUnserialized(): void
    {
        $className = PrototypeClassA::class;

        $singletonA = $this->objectManager->get(SingletonClassA::class);

        $prototypeA = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{}');
        self::assertSame($singletonA, $prototypeA->getSingletonA());
    }

    #[Test]
    public function virtualObjectsDefinedInObjectsYamlCanUseAFactoryForTheirActualImplementation(): void
    {
        $prototypeA = $this->objectManager->get(PrototypeClassAishInterface::class);

        # Note: The "someProperty" injection is defined in the Objects.yaml of the Flow package (Testing context)
        #       for the object "Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassAishInterface"
        self::assertInstanceOf(PrototypeClassA::class, $prototypeA);
        self::assertSame('value defined in Objects.yaml', $prototypeA->getSomeProperty());
    }

    #[Test]
    public function constructorInjectionInSingletonCanHandleArgumentDefinedInSettings(): void
    {
        $objectC = $this->objectManager->get(SingletonClassC::class);

        // Note: The "settingsArgument" is defined in the Settings.yaml of the Flow package (Testing context)
        self::assertSame('setting injected singleton value', $objectC->settingsArgument);
    }

    #[Test]
    public function singletonCanHandleInjectedPrototypeWithSettingArgument(): void
    {
        $objectD = $this->objectManager->get(SingletonClassD::class);

        // Note: The "settingsArgument" is defined in the Settings.yaml of the Flow package (testing context)
        self::assertSame('setting injected property value', $objectD->prototypeClassC->settingsArgument);
    }

    #[Test]
    public function singletonCanHandleInjectedPrototypeWithCustomFactory(): void
    {
        $objectD = $this->objectManager->get(SingletonClassD::class);

        // Note: The "prototypeClassA" is defined with a custom factory in the Objects.yaml of the Flow package (testing context)
        self::assertNotNull($objectD->prototypeClassA);
        self::assertSame('value defined in Objects.yaml', $objectD->prototypeClassA->getSomeProperty());
    }

    #[Test]
    public function singletonCanHandleConstructorArgumentWithCustomFactory(): void
    {
        $objectG = $this->objectManager->get(SingletonClassG::class);

        // Note: The "prototypeClassA" is defined with a custom factory in the Objects.yaml of the Flow package (testing context)
        self::assertNotNull($objectG->prototypeA);
        self::assertSame('Constructor injection with factory', $objectG->prototypeA->getSomeProperty());
    }

    #[Test]
    public function onCreationOfObjectInjectionInParentClassIsDoneOnlyOnce(): void
    {
        $prototypeDsub = $this->objectManager->get(PrototypeClassDsub::class);
        self::assertSame(1, $prototypeDsub->injectionRuns);
    }

    /**
     * See http://forge.typo3.org/issues/43659
     */
    #[Test]
    public function injectedPropertiesAreAvailableInInitializeObjectEvenIfTheClassHasBeenExtended(): void
    {
        $prototypeDsub = $this->objectManager->get(PrototypeClassDsub::class);
        self::assertFalse($prototypeDsub->injectedPropertyWasUnavailable);
    }

    #[Test]
    public function constructorsOfSingletonObjectsAcceptNullArguments(): void
    {
        $objectF = $this->objectManager->get(SingletonClassF::class);

        self::assertNull($objectF->getNullValue());
    }

    #[Test]
    public function constructorsOfPrototypeObjectsAcceptNullArguments(): void
    {
        $objectE = $this->objectManager->get(PrototypeClassE::class, null);

        self::assertNull($objectE->getNullValue());
    }

    #[Test]
    public function injectionOfObjectFromSameNamespace(): void
    {
        $nonNamespacedDependencies = new ClassWithNonNamespacedDependencies();
        $classB = $this->objectManager->get(SingletonClassB::class);
        self::assertSame($classB, $nonNamespacedDependencies->getSingletonClassB());
    }

    #[Test]
    public function injectionOfObjectFromSubNamespace(): void
    {
        $nonNamespacedDependencies = new ClassWithNonNamespacedDependencies();
        $aClassFromSubNamespace = $this->objectManager->get(AnotherClass::class);
        self::assertSame($aClassFromSubNamespace, $nonNamespacedDependencies->getClassFromSubNamespace());
    }

    #[Test]
    public function injectionOfAllSettings(): void
    {
        $classWithInjectedConfiguration = new ClassWithInjectedConfiguration();
        $actualSettings = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow');
        self::assertSame($actualSettings, $classWithInjectedConfiguration->getSettings());
    }

    #[Test]
    public function injectionOfSpecifiedPackageSettings(): void
    {
        $classWithInjectedConfiguration = new ClassWithInjectedConfiguration();

        $actualSettings = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow');
        self::assertSame($actualSettings, $classWithInjectedConfiguration->getInjectedSpecifiedPackageSettings());
    }

    #[Test]
    public function injectionOfCurrentPackageSettings(): void
    {
        $classWithInjectedConfiguration = new ClassWithInjectedConfiguration();

        $actualSettings = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow');
        self::assertSame($actualSettings, $classWithInjectedConfiguration->getInjectedCurrentPackageSettings());
    }

    #[Test]
    public function injectionOfNonExistingSettingsOverridesDefaultValue(): void
    {
        $classWithInjectedConfiguration = new ClassWithInjectedConfiguration();
        self::assertNull($classWithInjectedConfiguration->getNonExistingSetting());
    }

    #[Test]
    public function injectionOfSingleSettings(): void
    {
        $classWithInjectedConfiguration = new ClassWithInjectedConfiguration();
        self::assertSame('injected setting', $classWithInjectedConfiguration->getInjectedSettingA());
    }

    #[Test]
    public function injectionOfSingleSettingsFromSpecificPackage(): void
    {
        $classWithInjectedConfiguration = new ClassWithInjectedConfiguration();
        self::assertSame('injected setting', $classWithInjectedConfiguration->getInjectedSettingB());
    }

    #[Test]
    public function injectionOfConfigurationCallsRespectiveSetterIfItExists(): void
    {
        $classWithInjectedConfiguration = new ClassWithInjectedConfiguration();
        self::assertSame('INJECTED SETTING', $classWithInjectedConfiguration->getInjectedSettingWithSetter());
    }

    #[Test]
    public function injectionOfOtherConfigurationTypes(): void
    {
        $classWithInjectedConfiguration = new ClassWithInjectedConfiguration();
        self::assertSame($this->configurationManager->getConfiguration('Views'), $classWithInjectedConfiguration->getInjectedViewsConfiguration());
    }

    #[Test]
    public function injectionOfCaches(): void
    {
        $classWithInjectedCache = new ClassWithInjectedCache();
        self::assertSame($this->cacheManager->getCache('Flow_Monitor'), $classWithInjectedCache->getCacheInjectedViaAttribute());
        self::assertSame($this->cacheManager->getCache('Flow_Monitor'), $classWithInjectedCache->getCacheInjectedViaAnnotation());
    }

    /**
     * This test verifies the behaviour described in FLOW-175.
     *
     * Please note that this issue occurs ONLY when creating an object
     * with a dependency that itself takes an prototype-scoped object as
     * constructor argument and that dependency was explicitly configured
     * in the package's Objects.yaml.
     *
     * @see https://jira.neos.io/browse/FLOW-175
     */
    #[Test]
    public function transitivePrototypeDependenciesWithExplicitObjectConfigurationAreConstructedCorrectly(): void
    {
        $classWithTransitivePrototypeDependency = new ClassWithTransitivePrototypeDependency();
        self::assertEquals('Hello World!', $classWithTransitivePrototypeDependency->getTestValue());
    }

    #[Test]
    public function dependencyInjectionWorksForFinalClasses(): void
    {
        $object = $this->objectManager->get(FinalClassWithDependencies::class);
        self::assertInstanceOf(SingletonClassA::class, $object->dependency);
    }

    #[Test]
    public function noProxyClassIsGeneratedForClassesWhoseConstructorAutowiringIsDisabledViaSettings(): void
    {
        $object = new PrototypeClassH(
            new ValueObjectClassA('foo'),
            new ValueObjectClassB('bar')
        );
        self::assertNotInstanceOf(ProxyInterface::class, $object);

        $object = new PrototypeClassA();
        self::assertInstanceOf(ProxyInterface::class, $object);
    }

    #[Test]
    public function constructorSettingsInjectionViaInjectAnnotation(): void
    {
        $object = $this->objectManager->get(PrototypeClassL::class);
        self::assertInstanceOf(PrototypeClassL::class, $object);
        self::assertSame('injected setting', $object->value);

        $object = new PrototypeClassL('override');
        self::assertSame('override', $object->value);
    }

    /**
     * Test that proxy constructors support PHP 8+ named arguments (issue #3076)
     */
    #[Test]
    public function prototypeObjectsCanBeInstantiatedWithNamedArguments(): void
    {
        $valueObject = new Fixtures\ValueObjectClassA('test-value');
        $object = new Fixtures\ClassWithNamedConstructorArguments(
            valueObject: $valueObject,
            stringValue: 'named-value'
        );

        self::assertSame($valueObject, $object->getValueObject());
        self::assertSame('named-value', $object->getStringValue());
    }

    /**
     * Test that named arguments work with positional arguments
     */
    #[Test]
    public function prototypeObjectsCanBeInstantiatedWithPositionalArguments(): void
    {
        $valueObject = new Fixtures\ValueObjectClassA('test-value');
        $object = new Fixtures\ClassWithNamedConstructorArguments($valueObject, 'positional-value');

        self::assertSame($valueObject, $object->getValueObject());
        self::assertSame('positional-value', $object->getStringValue());
    }

    /**
     * Test that named arguments work with default values
     */
    #[Test]
    public function prototypeObjectsCanBeInstantiatedWithNamedArgumentsAndDefaults(): void
    {
        $valueObject = new Fixtures\ValueObjectClassA('test-value');
        $object = new Fixtures\ClassWithNamedConstructorArguments(valueObject: $valueObject);

        self::assertSame($valueObject, $object->getValueObject());
        self::assertSame('default', $object->getStringValue());
    }

    /**
     * Test that constructor injection still works with named argument support
     */
    #[Test]
    public function singletonObjectsWithConstructorInjectionStillWorkWithNamedArgumentSupport(): void
    {
        $singleton = $this->objectManager->get(Fixtures\SingletonWithConstructorInjection::class);

        self::assertInstanceOf(Fixtures\SingletonClassA::class, $singleton->getSingletonA());
        self::assertNull($singleton->getOptionalValue());
    }

    /**
     * Readonly properties which are not promoted constructor properties are assigned in the
     * constructor body of the original class – the proxy constructor must not interfere with that.
     */
    #[Test]
    public function nonPromotedReadonlyPropertiesAreSetWhenAProxiedObjectIsCreated(): void
    {
        $object = $this->objectManager->get(ClassWithNonPromotedReadonlyProperties::class);

        self::assertInstanceOf(ProxyInterface::class, $object);
        self::assertSame('the default label', $object->label);
        self::assertSame(84, $object->getNumber());
        self::assertInstanceOf(SingletonClassA::class, $object->getSingletonA());
    }

    #[Test]
    public function nonPromotedReadonlyPropertiesRespectConstructorArguments(): void
    {
        $object = $this->objectManager->get(ClassWithNonPromotedReadonlyProperties::class, 'a custom label', 21);

        self::assertSame('a custom label', $object->label);
        self::assertSame(42, $object->getNumber());
    }

    #[Test]
    public function nonPromotedReadonlyPropertiesOfProxiedObjectsCannotBeModified(): void
    {
        $object = $this->objectManager->get(ClassWithNonPromotedReadonlyProperties::class);

        $this->expectException(\Error::class);
        $object->label = 'a new label';
    }
}
