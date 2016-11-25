<?php
namespace Neos\Flow\Core\Booting;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Exception as FlowException;

/**
 * A boot sequence, consisting of individual steps, each of them initializing a
 * specific part of the application.
 *
 * @api
 */
class Sequence
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var array
     */
    protected $steps = [];

    /**
     * @param string $identifier
     */
    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Adds the given step to this sequence, to be executed after then step specified
     * by $previousStepIdentifier. If no previous step is specified, the new step
     * is added to the list of steps executed right at the start of the sequence.
     *
     * @param Step $step The new step to add
     * @param string $previousStepIdentifier The preceding step
     * @return void
     */
    public function addStep(Step $step, $previousStepIdentifier = 'start')
    {
        $this->steps[$previousStepIdentifier][] = $step;
    }

    /**
     * Removes all occurrences of the specified step from this sequence
     *
     * @param string $stepIdentifier
     * @return void
     * @throws FlowException
     */
    public function removeStep($stepIdentifier)
    {
        $removedOccurrences = 0;
        foreach ($this->steps as $previousStepIdentifier => $steps) {
            foreach ($steps as $step) {
                if ($step->getIdentifier() === $stepIdentifier) {
                    unset($this->steps[$previousStepIdentifier][$stepIdentifier]);
                    $removedOccurrences++;
                }
            }
        }
        if ($removedOccurrences === 0) {
            throw new FlowException(sprintf('Cannot remove sequence step with identifier "%s" because no such step exists in the given sequence.', $stepIdentifier), 1322591669);
        }
    }

    /**
     * Executes all steps of this sequence
     *
     * @param \Neos\Flow\Core\Bootstrap $bootstrap
     * @return void
     */
    public function invoke(Bootstrap $bootstrap)
    {
        if (isset($this->steps['start'])) {
            foreach ($this->steps['start'] as $step) {
                $this->invokeStep($step, $bootstrap);
            }
        }
    }

    /**
     * Invokes a single step of this sequence and also invokes all steps registered
     * to be executed after the given step.
     *
     * @param Step $step The step to invoke
     * @param \Neos\Flow\Core\Bootstrap $bootstrap
     * @return void
     */
    protected function invokeStep(Step $step, Bootstrap $bootstrap)
    {
        $bootstrap->getSignalSlotDispatcher()->dispatch(__CLASS__, 'beforeInvokeStep', [$step, $this->identifier]);
        $identifier = $step->getIdentifier();
        $step($bootstrap);
        $bootstrap->getSignalSlotDispatcher()->dispatch(__CLASS__, 'afterInvokeStep', [$step, $this->identifier]);
        if (isset($this->steps[$identifier])) {
            foreach ($this->steps[$identifier] as $followingStep) {
                $this->invokeStep($followingStep, $bootstrap);
            }
        }
    }
}
