<?php
namespace TYPO3\Flow\Mvc\Controller;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A generic Controller exception
 *
 * @api
 */
class Exception extends \TYPO3\Flow\Mvc\Exception
{
    /**
     * @var \TYPO3\Flow\Mvc\RequestInterface
     */
    protected $request;

    /**
     * Overwrites parent constructor to be able to inject current request object.
     *
     * @param string $message
     * @param integer $code
     * @param \Exception $previousException
     * @param \TYPO3\Flow\Mvc\RequestInterface $request
     * @see \Exception
     */
    public function __construct($message = '', $code = 0, \Exception $previousException = null, \TYPO3\Flow\Mvc\RequestInterface $request)
    {
        $this->request = $request;
        parent::__construct($message, $code, $previousException);
    }

    /**
     * Returns the request object that exception belongs to.
     *
     * @return \TYPO3\Flow\Mvc\RequestInterface
     */
    protected function getRequest()
    {
        return $this->request;
    }
}
