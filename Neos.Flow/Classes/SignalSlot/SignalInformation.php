<?php
declare(strict_types=1);

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

/**
 * A SignalInformation instance carries information about the signal that arrives
 * at a slot during a signal/slot dispatch operation.
 *
 * @api
 * @Flow\Proxy(false)
 */
final class SignalInformation
{
    /**
     * @var string
     */
    protected $signalClassName;

    /**
     * @var string
     */
    protected $signalName;

    /**
     * @var array
     */
    protected $signalArguments;

    public function __construct(string $signalClassName, string $signalName, array $signalArguments)
    {
        $this->signalClassName = $signalClassName;
        $this->signalName = $signalName;
        $this->signalArguments = $signalArguments;
    }

    public function getSignalClassName(): string
    {
        return $this->signalClassName;
    }

    public function getSignalName(): string
    {
        return $this->signalName;
    }

    public function getSignalArguments(): array
    {
        return $this->signalArguments;
    }

    /**
     *
     *
     * @param string $argumentName
     * @return mixed
     */
    public function getSignalArgument(string $argumentName)
    {
        return $this->signalArguments[$argumentName] ?? null;
    }
}
