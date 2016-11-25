<?php
namespace Neos\Flow\Tests\Behavior\Features\Bootstrap;

require_once(__DIR__ . '/SubProcess/SubProcess.php');

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Tests\Features\Bootstrap\SubProcess\SubProcess;
use Neos\Flow\Cache\CacheManager;
use PHPUnit_Framework_Assert as Assert;

/**
 * Class IsolatedBehatStepsTrait
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
            /** @var CacheManager $cacheManager */
            $cacheManager = $this->objectManager->get(CacheManager::class);
            if ($cacheManager->hasCache('Flow_Security_Authorization_Privilege_Method')) {
                $cacheManager->getCache('Flow_Security_Authorization_Privilege_Method')->flush();
            }

            $objectConfigurationCache = $cacheManager->getCache('Flow_Object_Configuration');
            $objectConfigurationCache->remove('allAspectClassesUpToDate');
            $objectConfigurationCache->remove('allCompiledCodeUpToDate');
            $cacheManager->getCache('Flow_Object_Classes')->flush();

            $configurationManager = $this->objectManager->get(ConfigurationManager::class);
            $configurationManager->flushConfigurationCache();

            $this->subProcess = new SubProcess($this->objectManager->getContext());
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
