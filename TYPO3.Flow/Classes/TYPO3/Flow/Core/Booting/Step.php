<?php
namespace TYPO3\Flow\Core\Booting;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;

/**
 * A Step within a Sequence
 *
 * @api
 */
class Step
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var mixed
     */
    protected $callback;

    /**
     * @param string $identifier
     * @param mixed $callback
     */
    public function __construct($identifier, $callback)
    {
        $this->identifier = $identifier;
        $this->callback = $callback;
    }

    /**
     * Invokes / executes this step
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public function __invoke(Bootstrap $bootstrap)
    {
        call_user_func($this->callback, $bootstrap);
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
}
