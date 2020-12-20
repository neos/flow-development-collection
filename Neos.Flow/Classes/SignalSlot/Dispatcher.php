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
    public function injectObjectManager(ObjectManagerInterface $objectManager): void
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Connects a signal with a slot.
     *
     * One slot can be connected with multiple signals by calling this method multiple times.
     *
     * When $passSignalInformation is true, the slot will be passed a string (EmitterClassName::signalName) as the last
     * parameter.
     *
     * @param string $signalClassName Name of the class containing the signal
     * @param string $signalName Name of the signal
     * @param mixed $slotClassNameOrObject Name of the class containing the slot or the instantiated class or a Closure object
     * @param string $slotMethodName Name of the method to be used as a slot. If $slotClassNameOrObject is a Closure object, this parameter is ignored
     * @param boolean $passSignalInformation If set to true, the last argument passed to the slot will be information about the signal (EmitterClassName::signalName)
     * @return void
     * @throws \InvalidArgumentException
     * @api
     * @see wire()
     */
    public function connect(string $signalClassName, string $signalName, $slotClassNameOrObject, string $slotMethodName = '', bool $passSignalInformation = true): void
    {
        $this->connectSignalToSlot($signalClassName, $signalName, $slotClassNameOrObject, $slotMethodName, $passSignalInformation, false);
    }

    /**
     * Connects a signal with a slot.
     *
     * One slot can be connected with multiple signals by calling this method multiple times.
     *
     * The slot will be passed a an instance of SignalInformation as the sole parameter.
     *
     * @param string $signalClassName Name of the class containing the signal
     * @param string $signalName Name of the signal
     * @param mixed $slotClassNameOrObject Name of the class containing the slot or the instantiated class or a Closure object
     * @param string $slotMethodName Name of the method to be used as a slot. If $slotClassNameOrObject is a Closure object, this parameter is ignored
     * @return void
     * @throws \InvalidArgumentException
     * @api
     * @see connect()
     * @see SignalInformation
     */
    public function wire(string $signalClassName, string $signalName, $slotClassNameOrObject, string $slotMethodName = ''): void
    {
        $this->connectSignalToSlot($signalClassName, $signalName, $slotClassNameOrObject, $slotMethodName, false, true);
    }

    /**
     * @param string $signalClassName
     * @param string $signalName
     * @param mixed $slotClassNameOrObject
     * @param string $slotMethodName
     * @param bool $passSignalInformation
     * @param bool $useSignalInformationObject
     * @return void
     */
    private function connectSignalToSlot(string $signalClassName, string $signalName, $slotClassNameOrObject, string $slotMethodName, bool $passSignalInformation, bool $useSignalInformationObject): void
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
            'passSignalInformation' => $passSignalInformation,
            'useSignalInformationObject' => $useSignalInformationObject
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
    public function dispatch(string $signalClassName, string $signalName, array $signalArguments = []): void
    {
        if (!isset($this->slots[$signalClassName][$signalName])) {
            return;
        }

        foreach ($this->slots[$signalClassName][$signalName] as $slotInformation) {
            $finalSignalArguments = $signalArguments;
            if (isset($slotInformation['object'])) {
                $object = $slotInformation['object'];
            } elseif (strpos($slotInformation['method'], '::') === 0) {
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
            if (!method_exists($object, $slotInformation['method'])) {
                throw new Exception\InvalidSlotException('The slot method ' . get_class($object) . '->' . $slotInformation['method'] . '() does not exist.', 1245673368);
            }

            if ($slotInformation['useSignalInformationObject'] === true) {
                call_user_func([$object, $slotInformation['method']], new SignalInformation($signalClassName, $signalName, $finalSignalArguments));
            } else {
                if ($slotInformation['passSignalInformation'] === true) {
                    $finalSignalArguments[] = $signalClassName . '::' . $signalName;
                }
                // Need to use call_user_func_array here, because $object may be the class name when the slot is a static method
                call_user_func_array([$object, $slotInformation['method']], $finalSignalArguments);
            }
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
    public function getSlots(string $signalClassName, string $signalName): array
    {
        return $this->slots[$signalClassName][$signalName] ?? [];
    }

    /**
     * Returns all signals with its slots
     *
     * @return array An array of arrays with slot information
     * @api
     */
    public function getSignals(): array
    {
        return $this->slots;
    }
}
