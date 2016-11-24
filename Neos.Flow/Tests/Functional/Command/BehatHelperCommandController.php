<?php
namespace Neos\Flow\Tests\Functional\Command;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */



use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Security\Context;

/**
 * A command controller used to execute behat steps in an isolated process.
 * Note: This command controller will only be loaded in Testing context!
 *
 * @see IsolatedBehatStepsTrait
 *
 * @Flow\Scope("singleton")
 */
class BehatHelperCommandController extends CommandController
{
    /**
     * @var ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var Context
     * @Flow\Inject
     */
    protected $securityContext;

    /**
     * @var PropertyMapper
     * @Flow\Inject
     */
    protected $propertyMapper;

    /**
     * Calls a behat step method
     *
     * @Flow\Internal
     * @param string $testHelperObjectName
     * @param string $methodName
     * @param boolean $withoutSecurityChecks
     */
    public function callBehatStepCommand($testHelperObjectName, $methodName, $withoutSecurityChecks = false)
    {
        $testHelper = $this->objectManager->get($testHelperObjectName);

        $rawMethodArguments = $this->request->getExceedingArguments();
        $mappedArguments = [];
        for ($i = 0; $i < count($rawMethodArguments); $i+=2) {
            $mappedArguments[] = $this->propertyMapper->convert($rawMethodArguments[$i+1], $rawMethodArguments[$i]);
        }

        $result = null;
        try {
            if ($withoutSecurityChecks === true) {
                $this->securityContext->withoutAuthorizationChecks(function () use ($testHelper, $methodName, $mappedArguments, &$result) {
                    $result = call_user_func_array([$testHelper, $methodName], $mappedArguments);
                });
            } else {
                $result = call_user_func_array([$testHelper, $methodName], $mappedArguments);
            }
        } catch (\Exception $exception) {
            $this->outputLine('EXCEPTION: %s %d %s in %s:%s %s', [get_class($exception), $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTraceAsString()]);
            return;
        }
        $this->output('SUCCESS: %s', [$result]);
    }
}
