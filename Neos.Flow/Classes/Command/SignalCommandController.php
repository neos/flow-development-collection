<?php
declare(strict_types=1);

namespace Neos\Flow\Command;

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
use Neos\Flow\SignalSlot\Dispatcher;

/**
 * Signal command controller for the Neos.Flow package
 *
 * @Flow\Scope("singleton")
 */
class SignalCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * Lists all connected signals with their slots.
     *
     * @param string $className if specified, only signals matching the given fully qualified class name will be shown. Note: escape namespace separators or wrap the value in quotes, e.g. "--class-name Neos\\Flow\\Core\\Bootstrap".
     * @param string $methodName if specified, only signals matching the given method name will be shown. This is only useful in conjunction with the "--class-name" option.
     * @return void
     */
    public function listConnectedCommand(string $className = null, string $methodName = null): void
    {
        $this->outputFormatted('<b>Connected signals with their slots.</b>');
        $this->outputLine();

        $connectedSignals = $this->dispatcher->getSignals();
        foreach ($connectedSignals as $signalClassName => $signalsByClass) {
            if ($className !== null && $signalClassName !== $className) {
                continue;
            }

            $this->outputFormatted('<b>%s</b>', [$signalClassName]);
            foreach ($signalsByClass as $signalMethodName => $slots) {
                if ($methodName !== null && $signalMethodName !== $methodName) {
                    continue;
                }

                $this->outputFormatted('<b>%s</b>', [$signalMethodName], 2);
                foreach ($slots as $slot) {
                    $slotClassName = $slot['class'];
                    $slotMethodName = $slot['method'];

                    if ($slotClassName !== null) {
                        $this->outputFormatted('%s::%s', [$slotClassName, $slotMethodName], 4);
                    } else {
                        $this->outputFormatted('<i>Closure</i>', [], 4);
                    }
                }
            }
            $this->outputLine();
        }
    }
}
