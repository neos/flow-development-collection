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

use Neos\Flow\Aop\Pointcut\Pointcut;

/**
 * Implementation of the interface introduction declaration.
 *
 */
class InterfaceIntroduction
{
    /**
     * Name of the aspect declaring this introduction
     * @var string
     */
    protected $declaringAspectClassName;

    /**
     * Name of the introduced interface
     * @var string
     */
    protected $interfaceName;

    /**
     * The pointcut this introduction applies to
     * @var Pointcut
     */
    protected $pointcut;

    /**
     * Constructor
     *
     * @param string $declaringAspectClassName Name of the aspect containing the declaration for this introduction
     * @param string $interfaceName Name of the interface to introduce
     * @param Pointcut $pointcut The pointcut for this introduction
     */
    public function __construct($declaringAspectClassName, $interfaceName, Pointcut $pointcut)
    {
        $this->declaringAspectClassName = $declaringAspectClassName;
        $this->interfaceName = $interfaceName;
        $this->pointcut = $pointcut;
    }

    /**
     * Returns the name of the introduced interface
     *
     * @return string Name of the introduced interface
     */
    public function getInterfaceName()
    {
        return $this->interfaceName;
    }

    /**
     * Returns the pointcut this introduction applies to
     *
     * @return Pointcut The pointcut
     */
    public function getPointcut()
    {
        return $this->pointcut;
    }

    /**
     * Returns the object name of the aspect which declared this introduction
     *
     * @return string The aspect object name
     */
    public function getDeclaringAspectClassName()
    {
        return $this->declaringAspectClassName;
    }
}
