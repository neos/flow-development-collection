<?php
namespace TYPO3\Flow\Aop;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


/**
 * An advisor is the combination of a single advice and the pointcut where the
 * advice will become active.
 *
 */
class Advisor
{
    /**
     * The advisor's advice
     * @var \TYPO3\Flow\Aop\Advice\AdviceInterface
     */
    protected $advice;

    /**
     * The pointcut for the advice
     * @var \TYPO3\Flow\Aop\Pointcut\Pointcut
     */
    protected $pointcut;

    /**
     * Initializes the advisor with an advice and a pointcut
     *
     * @param \TYPO3\Flow\Aop\Advice\AdviceInterface $advice The advice to weave in
     * @param \TYPO3\Flow\Aop\Pointcut\Pointcut $pointcut The pointcut where the advice should be inserted
     */
    public function __construct(\TYPO3\Flow\Aop\Advice\AdviceInterface $advice, \TYPO3\Flow\Aop\Pointcut\Pointcut $pointcut)
    {
        $this->advice = $advice;
        $this->pointcut = $pointcut;
    }

    /**
     * Returns the advisor's advice
     *
     * @return \TYPO3\Flow\Aop\Advice\AdviceInterface The advice
     */
    public function getAdvice()
    {
        return $this->advice;
    }

    /**
     * Returns the advisor's pointcut
     *
     * @return \TYPO3\Flow\Aop\Pointcut\Pointcut The pointcut
     */
    public function getPointcut()
    {
        return $this->pointcut;
    }
}
