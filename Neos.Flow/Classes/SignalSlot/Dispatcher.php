<?php
namespace Neos\Flow\SignalSlot;

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
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * A dispatcher which dispatches signals by calling its registered slot methods
 * and passing them the method arguments which were originally passed to the
 * signal method.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Dispatcher
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Information about all slots connected a certain signal.
     * Indexed by [$signalClassName][$signalMethodName] and then numeric with an
     * array of information about the slot
     * @var array
     */
    protected $slots = [];

    /**
     * Injects the object manager
     *
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Connects a signal with a slot.
     * One slot can be connected with multiple signals by calling this method multiple times.
     *
     * @param string $signalClassName Name of the class containing the signal
     * @param string $signalName Name of the signal
     * @param mixed $slotClassNameOrObject Name of the class containing the slot or the instantiated class or a Closure object
     * @param string $slotMethodName Name of the method to be used as a slot. If $slotClassNameOrObject is a Closure object, this parameter is ignored
     * @param boolean $passSignalInformation If set to TRUE, the last argument passed to the slot will be information about the signal (EmitterClassName::signalName)
     * @return void
     * @throws \InvalidArgumentException
     * @api
     */
    public function connect($signalClassName, $signalName, $slotClassNameOrObject, $slotMethodName = '', $passSignalInformation = true)
    {
        $class = null;
        $object = null;

        if (strpos($signalName, 'emit') === 0) {
            $possibleSignalName = lcfirst(substr($signalName, strlen('emit')));
            throw new \InvalidArgumentException('The signal should not be connected with the method name ("' . $signalName . '"). Try "' . $possibleSignalName . '" for the signal name.', 1314016630);
        }

        if (is_object($slotClassNameOrObject)) {
            $object = $slotClassNameOrObject;
            $method = ($slotClassNameOrObject instanceof \Closure) ? '__invoke' : $slotMethodName;
        } else {
            if ($slotMethodName === '') {
                throw new \InvalidArgumentException('The slot method name must not be empty (except for closures).', 1229531659);
            }
            $class = $slotClassNameOrObject;
            $method = $slotMethodName;
        }

        $this->slots[$signalClassName][$signalName][] = [
            'class' => $class,
            'method' => $method,
            'object' => $object,
            'passSignalInformation' => ($passSignalInformation === true)
        ];
    }

    /**
     * Dispatches a signal by calling the registered Slot methods
     *
     * @param string $signalClassName Name of the class containing the signal
     * @param string $signalName Name of the signal
     * @param array $signalArguments arguments passed to the signal method
     * @return void
     * @throws Exception\InvalidSlotException if the slot is not valid
     * @api
     */
    public function dispatch($signalClassName, $signalName, array $signalArguments = [])
    {
        if (!isset($this->slots[$signalClassName][$signalName])) {
            return;
        }

        foreach ($this->slots[$signalClassName][$signalName] as $slotInformation) {
            if (isset($slotInformation['object'])) {
                $object = $slotInformation['object'];
            } elseif (substr($slotInformation['method'], 0, 2) === '::') {
                if (!isset($this->objectManager)) {
                    if (is_callable($slotInformation['class'] . $slotInformation['method'])) {
                        $object = $slotInformation['class'];
                    } else {
                        throw new Exception\InvalidSlotException(sprintf('Cannot dispatch %s::%s to class %s. The object manager is not yet available in the Signal Slot Dispatcher and therefore it cannot dispatch classes.', $signalClassName, $signalName, $slotInformation['class']), 1298113624);
                    }
                } else {
                    $object = $this->objectManager->getClassNameByObjectName($slotInformation['class']);
                }
                $slotInformation['method'] = substr($slotInformation['method'], 2);
            } else {
                if (!isset($this->objectManager)) {
                    throw new Exception\InvalidSlotException(sprintf('Cannot dispatch %s::%s to class %s. The object manager is not yet available in the Signal Slot Dispatcher and therefore it cannot dispatch classes.', $signalClassName, $signalName, $slotInformation['class']), 1298113624);
                }
                if (!$this->objectManager->isRegistered($slotInformation['class'])) {
                    throw new Exception\InvalidSlotException('The given class "' . $slotInformation['class'] . '" is not a registered object.', 1245673367);
                }
                $object = $this->objectManager->get($slotInformation['class']);
            }
            if ($slotInformation['passSignalInformation'] === true) {
                $signalArguments[] = $signalClassName . '::' . $signalName;
            }
            if (!method_exists($object, $slotInformation['method'])) {
                throw new Exception\InvalidSlotException('The slot method ' . get_class($object) . '->' . $slotInformation['method'] . '() does not exist.', 1245673368);
            }
            call_user_func_array([$object, $slotInformation['method']], $signalArguments);
        }
    }

    /**
     * Returns all slots which are connected with the given signal
     *
     * @param string $signalClassName Name of the class containing the signal
     * @param string $signalName Name of the signal
     * @return array An array of arrays with slot information
     * @api
     */
    public function getSlots($signalClassName, $signalName)
    {
        return (isset($this->slots[$signalClassName][$signalName])) ? $this->slots[$signalClassName][$signalName] : [];
    }

    /**
     * Returns all signals with its slots
     *
     * @return array An array of arrays with slot information
     * @api
     */
    public function getSignals()
    {
        return $this->slots;
    }
}
