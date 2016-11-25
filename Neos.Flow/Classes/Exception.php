<?php
namespace Neos\Flow;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A generic Flow Exception
 *
 * @api
 */
class Exception extends \Exception
{
    /**
     * @var string
     */
    protected $referenceCode;

    /**
     * @var integer
     */
    protected $statusCode = 500;

    /**
     * Returns a code which can be communicated publicly so that whoever experiences the exception can refer
     * to it and a developer can find more information about it in the system log.
     *
     * @return string
     * @api
     */
    public function getReferenceCode()
    {
        if (!isset($this->referenceCode)) {
            $this->referenceCode = date('YmdHis', $_SERVER['REQUEST_TIME']) . substr(md5(rand()), 0, 6);
        }
        return $this->referenceCode;
    }

    /**
     * Returns the HTTP status code this exception corresponds to (defaults to 500).
     *
     * @return integer
     * @api
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
