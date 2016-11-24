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
 * Implementation of the property introduction declaration.
 *
 */
class PropertyIntroduction
{
    /**
     * Name of the aspect declaring this introduction
     * @var string
     */
    protected $declaringAspectClassName;

    /**
     * Name of the introduced property
     * @var string
     */
    protected $propertyName;

    /**
     * Visibility of the introduced property
     * @var string
     */
    protected $propertyVisibility;

    /**
     * The initial value of the property
     * @var mixed
     */
    protected $initialValue;

    /**
     * DocComment of the introduced property
     * @var string
     */
    protected $propertyDocComment;

    /**
     * The pointcut this introduction applies to
     * @var Pointcut
     */
    protected $pointcut;

    /**
     * Constructor
     *
     * @param string $declaringAspectClassName Name of the aspect containing the declaration for this introduction
     * @param string $propertyName Name of the property to introduce
     * @param Pointcut $pointcut The pointcut for this introduction
     */
    public function __construct($declaringAspectClassName, $propertyName, Pointcut $pointcut)
    {
        $this->declaringAspectClassName = $declaringAspectClassName;
        $this->propertyName = $propertyName;
        $this->pointcut = $pointcut;

        $propertyReflection = new \ReflectionProperty($declaringAspectClassName, $propertyName);
        $classReflection = new \ReflectionClass($declaringAspectClassName);
        $defaultProperties = $classReflection->getDefaultProperties();
        $this->initialValue = $defaultProperties[$propertyName];

        if ($propertyReflection->isPrivate()) {
            $this->propertyVisibility = 'private';
        } elseif ($propertyReflection->isProtected()) {
            $this->propertyVisibility = 'protected';
        } else {
            $this->propertyVisibility = 'public';
        }
        $this->propertyDocComment = preg_replace('/@(Neos\\\\Flow\\\\Annotations|Flow)\\\\Introduce.+$/mi', 'introduced by ' . $declaringAspectClassName, $propertyReflection->getDocComment());
    }

    /**
     * Returns the name of the introduced property
     *
     * @return string Name of the introduced property
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * Returns the visibility of the introduced property
     *
     * @return string Visibility of the introduced property
     */
    public function getPropertyVisibility()
    {
        return $this->propertyVisibility;
    }

    /**
     * @return mixed
     */
    public function getInitialValue()
    {
        return $this->initialValue;
    }

    /**
     * Returns the DocComment of the introduced property
     *
     * @return string DocComment of the introduced property
     */
    public function getPropertyDocComment()
    {
        return $this->propertyDocComment;
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
