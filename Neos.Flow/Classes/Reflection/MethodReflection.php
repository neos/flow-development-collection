<?php
namespace Neos\Flow\Reflection;

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

/**
 * Extended version of the ReflectionMethod
 *
 * @Flow\Proxy(false)
 */
class MethodReflection extends \ReflectionMethod
{
    /**
     * @var DocCommentParser: An instance of the doc comment parser
     */
    protected $docCommentParser;

    /**
     * Returns the declaring class
     *
     * @return ClassReflection The declaring class
     */
    public function getDeclaringClass()
    {
        return new ClassReflection(parent::getDeclaringClass()->getName());
    }

    /**
     * Replacement for the original getParameters() method which makes sure
     * that ParameterReflection objects are returned instead of the
     * original ReflectionParameter instances.
     *
     * @return array<ParameterReflection> objects of the parameters of this method
     */
    public function getParameters()
    {
        $extendedParameters = [];
        foreach (parent::getParameters() as $parameter) {
            $extendedParameters[] = new ParameterReflection([$this->getDeclaringClass()->getName(), $this->getName()], $parameter->getName());
        }
        return $extendedParameters;
    }

    /**
     * Checks if the doc comment of this method is tagged with
     * the specified tag
     *
     * @param string $tag Tag name to check for
     * @return boolean TRUE if such a tag has been defined, otherwise FALSE
     */
    public function isTaggedWith($tag)
    {
        return $this->getDocCommentParser()->isTaggedWith($tag);
    }

    /**
     * Returns an array of tags and their values
     *
     * @return array Tags and values
     */
    public function getTagsValues()
    {
        return $this->getDocCommentParser()->getTagsValues();
    }

    /**
     * Returns the values of the specified tag
     *
     * @param string $tag Tag name to check for
     * @return array Values of the given tag
     */
    public function getTagValues($tag)
    {
        return $this->getDocCommentParser()->getTagValues($tag);
    }

    /**
     * Returns the description part of the doc comment
     *
     * @return string Doc comment description
     */
    public function getDescription()
    {
        return $this->getDocCommentParser()->getDescription();
    }

    /**
     * @return string The name of a type (e.g. string, \stdClass) if it was declared as a return type, null otherwise
     */
    public function getDeclaredReturnType()
    {
        if (!is_callable(array($this, 'getReturnType'))) {
            return null;
        }
        $type = $this->getReturnType();
        return $type !== null ? (string)$type : null;
    }

    /**
     * Returns an instance of the doc comment parser and
     * runs the parse() method.
     *
     * @return DocCommentParser
     */
    protected function getDocCommentParser()
    {
        if (!is_object($this->docCommentParser)) {
            $this->docCommentParser = new DocCommentParser;
            $this->docCommentParser->parseDocComment($this->getDocComment());
        }
        return $this->docCommentParser;
    }
}
