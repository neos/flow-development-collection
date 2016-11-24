<?php
namespace Neos\Flow\Aop;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Aop\Advice\AdviceInterface;
use Neos\Flow\Aop\Pointcut\Pointcut;

/**
 * An advisor is the combination of a single advice and the pointcut where the
 * advice will become active.
 *
 */
class Advisor
{
    /**
     * The advisor's advice
     * @var AdviceInterface
     */
    protected $advice;

    /**
     * The pointcut for the advice
     * @var Pointcut
     */
    protected $pointcut;

    /**
     * Initializes the advisor with an advice and a pointcut
     *
     * @param AdviceInterface $advice The advice to weave in
     * @param Pointcut $pointcut The pointcut where the advice should be inserted
     */
    public function __construct(AdviceInterface $advice, Pointcut $pointcut)
    {
        $this->advice = $advice;
        $this->pointcut = $pointcut;
    }

    /**
     * Returns the advisor's advice
     *
     * @return AdviceInterface The advice
     */
    public function getAdvice()
    {
        return $this->advice;
    }

    /**
     * Returns the advisor's pointcut
     *
     * @return Pointcut The pointcut
     */
    public function getPointcut()
    {
        return $this->pointcut;
    }
}
