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

use Neos\Eel\Exception;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;

/**
 * FlowQuery is jQuery for PHP, a selector and traversal engine for object sets.
 *
 * It is specifically implemented for being used inside Eel, the Embedded Expression
 * Language for Flow.
 *
 * Essentially, a FlowQuery object is a container for an *ordered set* of objects
 * of a certain type. On this container, *operations* can be applied (like
 * filter(), children(), ...).
 *
 * All of these operations work on a *set*, that is, an operation usually expands
 * or shrinks the set of objects.
 *
 * An operation normally returns a new FlowQuery instance with the operation applied,
 * but there are also some operations like is(...) or count(), which return simple
 * types like boolean or numbers. We call these operations *final operations*.
 *
 * Internal Workings
 * =================
 *
 * To allow for optimization, calling operations are not immediately executed.
 * Instead, they are appended to a *list of operations*. Only if one tries to
 * iterate over the FlowQuery object or calls a final operation, the operations
 * get executed and the result is computed.
 *
 * Implementation of Operations
 * ----------------------------
 *
 * Operations are implemented by implementing the {@link OperationInterface} or,
 * more commonly, subclassing the {@link Operations/AbstractOperation}.
 *
 * An operation must be *equivalence preserving*, that is, the following equation
 * must always hold:
 *
 * applyAllOperations(context) = applyRemainingOperations(applyOperation(context))
 *
 * While an operation is running, it can *add new operations* to the front of the
 * operation queue (with {@link pushOperation()}), so for example count($filter)
 * can first apply filter($filter), followed by count(). However, when doing this,
 * great care must be applied by the operation developer in order to still have
 * finite runs, and to make sure the operation is *equivalence preserving*.
 *
 * Furthermore, an operation can *pop* its following operations from the operation
 * stack, and *peek* what the next operation is. It is up to the operation developer
 * to ensure equivalence preservation.
 *
 * An operation may *never* invoke __call() on the FlowQuery object it receives;
 * as this might lead to an undefined state; i.e. you are not allowed to do:
 * $flowQuery->someOtherOperation() *inside* an operation.
 *
 * Final Operations
 * ----------------
 *
 * If an operation is final, it should return the resulting value directly.
 */
class FlowQuery implements ProtectedContextAwareInterface, \IteratorAggregate, \Countable
{
    /**
     * the objects this FlowQuery object wraps
     *
     * @var array|\Traversable
     */
    protected $context;

    /**
     * Ordered list of operations, each operation is internally
     * represented as array('name' => '...', 'arguments' => array(...))
     * whereas the name is a string like 'children' and the arguments
     * are a numerically-indexed array
     *
     * @var array
     */
    protected $operations = [];

    /**
     * @Flow\Inject
     * @var OperationResolverInterface
     */
    protected $operationResolver;

    /**
     * Construct a new FlowQuery object from $context and $operations.
     *
     * Only the $context parameter belongs to the public API!
     *
     * If a FlowQuery is given as the $context we unwrap its context to assert q(q(context)) == q(context).
     *
     * @param array|\Traversable $context The initial context (wrapped objects) for this FlowQuery
     * @param array              $operations
     * @throws Exception
     * @api
     */
    public function __construct($context, array $operations = [])
    {
        if (!(is_array($context) || $context instanceof \Traversable)) {
            throw new Exception('The FlowQuery context must be an array or implement \Traversable but context was a ' . gettype($context), 1380816689);
        }
        if ($context instanceof FlowQuery) {
            $this->context = $context->getContext();
        } else {
            $this->context = $context;
        }
        $this->operations = $operations;
    }

    /**
     * Setter for setting the operation resolver from the outside, only needed
     * to successfully run unit tests (hacky!)
     *
     * @param OperationResolverInterface $operationResolver
     */
    public function setOperationResolver(OperationResolverInterface $operationResolver)
    {
        $this->operationResolver = $operationResolver;
    }

    /**
     * Add a new operation to the operation list and return the new FlowQuery
     * object. If the operation is final, we directly compute the result and
     * return the value.
     *
     * @param string $operationName
     * @param array $arguments
     * @return FlowQuery
     */
    public function __call($operationName, array $arguments)
    {
        $updatedOperations = $this->operations;
        $updatedOperations[] = [
            'name' => $operationName,
            'arguments' => $arguments
        ];

        if ($this->operationResolver->isFinalOperation($operationName)) {
            $operationsBackup = $this->operations;
            $contextBackup = $this->context;

            $this->operations = $updatedOperations;
            $operationResult = $this->evaluateOperations();
            $this->operations = $operationsBackup;
            $this->context = $contextBackup;

            return $operationResult;
        } else {
            // non-final operation
            $flowQuery = new FlowQuery($this->context, $updatedOperations);
            $flowQuery->setOperationResolver($this->operationResolver); // Only needed for unit tests; hacky!
            return $flowQuery;
        }
    }

    /**
     * Implementation of the countable() interface, which is mapped to the "count" operation.
     *
     * @return integer
     */
    public function count()
    {
        return $this->__call('count', []);
    }

    /**
     * Called when iterating over this FlowQuery object, triggers evaluation.
     *
     * Should NEVER be called inside an operation!
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        if (count($this->operations) > 0) {
            $this->evaluateOperations();
        }
        return new \ArrayIterator($this->context);
    }

    /**
     * Evaluate operations
     *
     * @return mixed the last operation result if the operation is a final operation, NULL otherwise
     */
    protected function evaluateOperations()
    {
        while ($op = array_shift($this->operations)) {
            $operation = $this->operationResolver->resolveOperation($op['name'], $this->context);
            $lastOperationResult = $operation->evaluate($this, $op['arguments']);
        }
        return $lastOperationResult;
    }

    /**
     * Pop the topmost operation from the stack and return it; i.e. the
     * operation which should be executed next. The returned array has
     * the form:
     * array('name' => '...', 'arguments' => array(...))
     *
     * Should only be called inside an operation.
     *
     * @return array
     */
    public function popOperation()
    {
        return array_shift($this->operations);
    }

    /**
     * Push a new operation onto the operations stack.
     *
     * The last-pushed operation is executed FIRST! (LIFO)
     *
     * Should only be called inside an operation.
     *
     * @param string $operationName
     * @param array $arguments
     */
    public function pushOperation($operationName, array $arguments)
    {
        array_unshift($this->operations, [
            'name' => $operationName,
            'arguments' => $arguments
        ]);
    }

    /**
     * Peek onto the next operation name, if any, or NULL otherwise.
     *
     * Should only be called inside an operation.
     *
     * @return string the next operation name or NULL if no next operation found.
     */
    public function peekOperationName()
    {
        if (isset($this->operations[0])) {
            return $this->operations[0]['name'];
        } else {
            return null;
        }
    }

    /**
     * Get the current context.
     *
     * Should only be called inside an operation.
     *
     * @return array|\Traversable
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set the updated context with the operation result applied.
     *
     * Should only be called inside an operation.
     *
     * @param array|\Traversable $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return $this->operationResolver->hasOperation($methodName);
    }
}
