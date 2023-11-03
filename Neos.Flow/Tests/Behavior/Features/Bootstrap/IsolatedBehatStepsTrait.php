<?php
namespace Neos\Flow\Tests\Behavior\Features\Bootstrap;

require_once(__DIR__ . '/SubProcess/SubProcess.php');

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\Features\Bootstrap\SubProcess\SubProcess;
use Neos\Flow\Cache\CacheManager;
use PHPUnit\Framework\Assert;

/**
 * ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
 * ┃ Flow's isolated behat tests  ┃
 * ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
 *
 * Infrastructure for isolated security testing.
 * Security features mostly have to be tested in a subprocess,
 * as we have to recreate proxies for the specified testing policy.
 *
 * For example this feature would be executed the following way:
 *
 * ```
 * ┌──────────────────────────────────────────────┐
 * │ Feature (Policy.feature)                     │
 * │                                              │
 * │ @Isolated                                    │
 * │ Scenario: Action x is granted for y          │
 * │   Given I have the following policies:       │
 * │     """yaml                                  │
 * │     privilegeTargets: ...                    │
 * │     roles: ...                               │
 * │     """                                      │
 * │   And I am authenticated with role "Foo:Bar" │
 * │   Then I can call the method "barAction"     │
 * └──────────────────────────────────────────────┘
 * ```
 *
 * 1. Behat boots up FeatureContext and Flow with proxies compiled in Testing/Behat context.
 * The @Isolated tag sets the state $isolated to true.
 *
 * 2. The step "I have the following policies" places the additional Policy.yaml at a temporary path,
 * which will be used for subsequent Flow boots.
 *
 * 3. The following steps cannot be executed in the current php process, as we need to
 * recompile Flow and completely reboot.
 * The $isolated=true state declares that the next steps being run should start a subprocess
 * where the proxies files are adjusted.
 *
 * ┌───────────────────────┐
 * │  Subprocess handling  │
 * └───────────────────────┘
 *
 * The step "I can call the method" switches behavior based on the $isolated flag.
 * Its important that the logic is placed in a trait like "SecurityOperationsTrait"
 * {@see SecurityOperationsTrait}, which can later be used from within the subprocess.
 *
 * ```
 * ┌───────────────────────────────────────────────────────────────────────┐
 * │ public function iCanCallTheMethod($method)                            │
 * │ {                                                                     │
 * │     if ($this->isolated === true) {                                   │
 * │         $this->callStepInSubProcess(__METHOD__, 'string ' . $method); │
 * │     } else {                                                          │
 * │         // actual logic ...                                           │
 * │         $someInstance->$method();                                     │
 * │     }                                                                 │
 * │ }                                                                     │
 * └───────────────────────────────────────────────────────────────────────┘
 * ```
 *
 * Case: $isolated=true
 * ────────────────────
 *
 * We get or initialized the handle for a subprocess {@see Subprocess} for this scenario.
 *
 * The command `behathelper:callbehatstep` {@see BehatHelperCommandController::callBehatStepCommand}
 * will be called with the arguments like "iCanCallTheMethod" and its encoded parameters
 * as well as with the class-string property $behatTestHelperObjectName of the initial FeatureContext.
 *
 * In this subprocess we get the singleton of name $behatTestHelperObjectName
 * and call the method "iCanCallTheMethod" on it with its parameters.
 *
 * The behat test helper {@see BehatTestHelper} composes the same trait "SecurityOperationsTrait"
 * like our feature context. To execute inside this behat helper the actual step,
 * we set the isolation state always to false: $isolated=false.
 *
 * {@see IsolatedBehatStepsTrait::callStepInSubProcess}
 *
 * Case: $isolated=false
 * ─────────────────────
 *
 * The actual logic of this trait is run but in a subprocess.
 *
 * ───────────────
 * End of test run
 * ───────────────
 *
 * The test was run successfully in a subprocess.
 * Each new scenario will close the pipe to the subprocess and start a new one.
 *
 * @deprecated todo the policy features depending on this handcrafted isolated behat test infrastructure will be refactored and this infrastructure removed.
 * @internal only allowed to be used internally for Neos.Flow behavioral tests!
 */
trait IsolatedBehatStepsTrait
{
    /**
     * @var boolean
     */
    protected $isolated = false;

    /**
     * @var SubProcess
     */
    protected $subProcess;

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract private function getObject(string $className): object;

    /**
     * @BeforeScenario @Isolated
     * @return void
     */
    public function setIsolatedFlag()
    {
        $this->isolated = true;
    }

    /**
     * @return SubProcess
     */
    protected function getSubProcess()
    {
        if ($this->subProcess === null) {
            $cacheManager = $this->getObject(CacheManager::class);
            if ($cacheManager->hasCache('Flow_Security_Authorization_Privilege_Method')) {
                $cacheManager->getCache('Flow_Security_Authorization_Privilege_Method')->flush();
            }

            $objectConfigurationCache = $cacheManager->getCache('Flow_Object_Configuration');
            $objectConfigurationCache->remove('allAspectClassesUpToDate');
            $objectConfigurationCache->remove('allCompiledCodeUpToDate');
            $cacheManager->getCache('Flow_Object_Classes')->flush();

            $configurationManager = $this->getObject(ConfigurationManager::class);
            $configurationManager->flushConfigurationCache();

            $this->subProcess = new SubProcess($this->getObject(ObjectManagerInterface::class)->getContext());
        }
        return $this->subProcess;
    }

    /**
     * @param $stepMethodName string
     * @param $encodedStepArguments string
     */
    protected function callStepInSubProcess($stepMethodName, $encodedStepArguments = '', $withoutSecurityChecks = false)
    {
        if (strpos($stepMethodName, '::') !== 0) {
            $stepMethodName = substr($stepMethodName, strpos($stepMethodName, '::') + 2);
        }
        $withoutSecurityChecks = ($withoutSecurityChecks === true ? '--without-security-checks ' : '');
        /** {@see \Neos\Flow\Tests\Functional\Command\BehatHelperCommandController} */
        $subProcessCommand = sprintf('neos.flow.tests.functional:behathelper:callbehatstep %s%s %s%s', $withoutSecurityChecks, escapeshellarg($this->behatTestHelperObjectName), $stepMethodName, $encodedStepArguments);

        $subProcessResponse = $this->getSubProcess()->execute($subProcessCommand);

        Assert::assertStringStartsWith('SUCCESS:', $subProcessResponse, 'We called "' . $subProcessCommand . '" and got: ' . $subProcessResponse);
    }

    /**
     * @AfterScenario
     */
    public function quitSubProcess()
    {
        if ($this->subProcess !== null) {
            $this->subProcess->quit();
        }
    }
}
