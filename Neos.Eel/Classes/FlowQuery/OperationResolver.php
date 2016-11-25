<?php
namespace Neos\Eel\FlowQuery;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;

/**
 * FlowQuery Operation Resolver
 *
 * @Flow\Scope("singleton")
 */
class OperationResolver implements OperationResolverInterface
{
    /**
     * @var ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var ReflectionService
     * @Flow\Inject
     */
    protected $reflectionService;

    /**
     * 2-dimensional array of registered operations:
     * shortOperationName => priority => operation class name
     *
     * @var array
     */
    protected $operations = [];

    /**
     * associative array of registered final operations:
     * shortOperationName => shortOperationName
     *
     * @var array
     */
    protected $finalOperationNames = [];

    /**
     * Initializer, building up $this->operations and $this->finalOperationNames
     */
    public function initializeObject()
    {
        $operationsAndFinalOperationNames = static::buildOperationsAndFinalOperationNames($this->objectManager);
        $this->operations = $operationsAndFinalOperationNames[0];
        $this->finalOperationNames = $operationsAndFinalOperationNames[1];
    }

    /**
     * @param ObjectManagerInterface $objectManager
     * @return array Array of sorted operations and array of final operation names
     * @throws FlowQueryException
     * @Flow\CompileStatic
     */
    public static function buildOperationsAndFinalOperationNames($objectManager)
    {
        $operations = [];
        $finalOperationNames = [];

        $reflectionService = $objectManager->get(ReflectionService::class);
        $operationClassNames = $reflectionService->getAllImplementationClassNamesForInterface(OperationInterface::class);
        /** @var $operationClassName OperationInterface */
        foreach ($operationClassNames as $operationClassName) {
            $shortOperationName = $operationClassName::getShortName();
            $operationPriority = $operationClassName::getPriority();
            $isFinalOperation = $operationClassName::isFinal();

            if (!isset($operations[$shortOperationName])) {
                $operations[$shortOperationName] = [];
            }

            if (isset($operations[$shortOperationName][$operationPriority])) {
                throw new FlowQueryException(sprintf('Operation with name "%s" and priority %s is already defined in class %s, and the class %s has the same priority and name.', $shortOperationName, $operationPriority, $operations[$shortOperationName][$operationPriority], $operationClassName), 1332491678);
            }
            $operations[$shortOperationName][$operationPriority] = $operationClassName;

            if ($isFinalOperation) {
                $finalOperationNames[$shortOperationName] = $shortOperationName;
            }
        }

        foreach ($operations as &$operation) {
            krsort($operation, SORT_NUMERIC);
        }

        return [$operations, $finalOperationNames];
    }

    /**
     * @param string $operationName
     * @return boolean TRUE if $operationName is final
     */
    public function isFinalOperation($operationName)
    {
        return isset($this->finalOperationNames[$operationName]);
    }

    /**
     * Resolve an operation, taking runtime constraints into account.
     *
     * @param string      $operationName
     * @param array|mixed $context
     * @throws FlowQueryException
     * @return OperationInterface the resolved operation
     */
    public function resolveOperation($operationName, $context)
    {
        if (!isset($this->operations[$operationName])) {
            throw new FlowQueryException('Operation "' . $operationName . '" not found.', 1332491837);
        }

        foreach ($this->operations[$operationName] as $operationClassName) {
            /** @var OperationInterface $operation */
            $operation = $this->objectManager->get($operationClassName);
            if ($operation->canEvaluate($context)) {
                return $operation;
            }
        }
        throw new FlowQueryException('No operation which satisfies the runtime constraints found for "' . $operationName . '".', 1332491864);
    }

    /**
     * @param string $operationName
     * @return boolean
     */
    public function hasOperation($operationName)
    {
        return isset($this->operations[$operationName]);
    }
}
