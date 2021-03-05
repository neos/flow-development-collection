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
 * Implementation of the trait introduction declaration.
 *
 */
class TraitIntroduction
{
    /**
     * Name of the aspect declaring this introduction
     * @var string
     */
    protected $declaringAspectClassName;

    /**
     * Name of the introduced trait
     * @var string
     */
    protected $traitName;

    /**
     * The pointcut this introduction applies to
     *
     * @var Pointcut
     */
    protected $pointcut;

    /**
     * Constructor
     *
     * @param string $declaringAspectClassName Name of the aspect containing the declaration for this introduction
     * @param string $traitName Name of the trait to introduce
     * @param Pointcut $pointcut The pointcut for this introduction
     */
    public function __construct(string $declaringAspectClassName, string $traitName, Pointcut $pointcut)
    {
        $this->declaringAspectClassName = $declaringAspectClassName;
        $this->traitName = $traitName;
        $this->pointcut = $pointcut;
    }

    /**
     * Returns the name of the introduced trait
     *
     * @return string Name of the introduced trait
     */
    public function getTraitName(): string
    {
        return $this->traitName;
    }

    /**
     * Returns the pointcut this introduction applies to
     *
     * @return Pointcut The pointcut
     */
    public function getPointcut(): Pointcut
    {
        return $this->pointcut;
    }

    /**
     * Returns the object name of the aspect which declared this introduction
     *
     * @return string The aspect object name
     */
    public function getDeclaringAspectClassName(): string
    {
        return $this->declaringAspectClassName;
    }
}
