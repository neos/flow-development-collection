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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;

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
