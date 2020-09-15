<?php
namespace Neos\Flow\Mvc\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\ActionRequest;

/**
 * A generic Controller exception
 *
 * @api
 */
class Exception extends \Neos\Flow\Mvc\Exception
{
    /**
     * @var ActionRequest
     */
    protected $request;

    /**
     * Overwrites parent constructor to be able to inject current request object.
     *
     * @param string $message
     * @param integer $code
     * @param \Exception $previousException
     * @param ActionRequest $request
     * @see \Exception
     */
    public function __construct($message = '', $code = 0, \Exception $previousException = null, ActionRequest $request)
    {
        $this->request = $request;
        parent::__construct($message, $code, $previousException);
    }

    /**
     * Returns the request object that exception belongs to.
     *
     * @return ActionRequest
     */
    protected function getRequest()
    {
        return $this->request;
    }
}
