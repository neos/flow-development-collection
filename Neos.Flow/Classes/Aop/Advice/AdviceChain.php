<?php
namespace Neos\Flow\Aop\Advice;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Aop\JoinPointInterface;

/**
 * The advice chain holds a number of subsequent advices that
 * match a given join point and calls the advices in the right order.
 *
 */
class AdviceChain
{
    /**
     * An array of Advice objects which form the advice chain
     * @var array
     */
    protected $advices;

    /**
     * The number of the next advice which will be invoked on a proceed() call
     * @var integer
     */
    protected $adviceIndex = -1;

    /**
     * Initializes the advice chain
     *
     * @param array $advices An array of AdviceInterface compatible objects which form the chain of advices
     */
    public function __construct($advices)
    {
        $this->advices = $advices;
    }

    /**
     * An advice usually calls (but doesn't have to necessarily) this method
     * in order to proceed with the next advice in the chain. If no advice is
     * left in the chain, the proxy classes' method invokeJoinpoint() will finally
     * be called.
     *
     * @param  JoinPointInterface $joinPoint The current join point (ie. the context)
     * @return mixed Result of the advice or the original method of the target class
     */
    public function proceed(JoinPointInterface &$joinPoint)
    {
        $this->adviceIndex++;
        if ($this->adviceIndex < count($this->advices)) {
            $result = $this->advices[$this->adviceIndex]->invoke($joinPoint);
        } else {
            $result = $joinPoint->getProxy()->Flow_Aop_Proxy_invokeJoinpoint($joinPoint);
        }
        return $result;
    }

    /**
     * Re-initializes the index to start a new run through the advice chain
     *
     * @return void
     */
    public function rewind()
    {
        $this->adviceIndex = -1;
    }
}
