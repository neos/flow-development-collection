<?php

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
     * @param string $className
     * @param string $methodName
     * @return void
     */
    public function listConnectedCommand(string $className = null,string $methodName = null)
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
                for ($i = 0; count($slots) > $i; $i++) {
                    $slotClassName = $slots[$i]['class'];
                    $slotMethodName = $slots[$i]['method'];

                    if ($slotClassName !== null) {
                        $this->outputFormatted('[%d] %s::%s', [$i, $slotClassName, $slotMethodName], 4);
                    } else {
                        $this->outputFormatted('[%d] <i>Closure</i>', [$i], 4);
                    }
                }
            }
            $this->outputLine();
        }
    }
}
