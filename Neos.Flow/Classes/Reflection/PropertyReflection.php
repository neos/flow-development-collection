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
 * Extended version of the ReflectionProperty
 *
 * @Flow\Proxy(false)
 */
class PropertyReflection extends \ReflectionProperty
{
    /**
     * @var DocCommentParser: An instance of the doc comment parser
     */
    protected $docCommentParser;

    /**
     * Whether this property represents an AOP-introduced property
     *
     * @var boolean
     */
    protected $isAopIntroduced = false;

    /**
     * Checks if the doc comment of this property is tagged with
     * the specified tag
     *
     * @param string $tag Tag name to check for
     * @return boolean TRUE if such a tag has been defined, otherwise FALSE
     */
    public function isTaggedWith($tag)
    {
        $result = $this->getDocCommentParser()->isTaggedWith($tag);
        return $result;
    }

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
     * @param string $tag
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
     * Returns the value of the reflected property - even if it is protected.
     *
     * @param object $object Instance of the declaring class to read the value from
     * @return mixed Value of the property
     * @throws Exception
     */
    public function getValue($object = null)
    {
        if (!is_object($object)) {
            throw new Exception('$object is of type ' . gettype($object) . ', instance of class ' . $this->class . ' expected.', 1210859212);
        }
        if ($this->isPublic()) {
            return parent::getValue($object);
        }

        parent::setAccessible(true);
        return parent::getValue($object);
    }

    /**
     * Returns the value of the reflected property - even if it is protected.
     *
     * @param object $object Instance of the declaring class to set the value on
     * @param mixed $value The value to set on the property
     * @return void
     * @throws Exception
     */
    public function setValue($object = null, $value = null)
    {
        if (!is_object($object)) {
            throw new Exception('$object is of type ' . gettype($object) . ', instance of class ' . $this->class . ' expected.', 1210859212);
        }

        if ($this->isPublic()) {
            parent::setValue($object, $value);
        } else {
            parent::setAccessible(true);
            parent::setValue($object, $value);
        }
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

    /**
     * @param boolean $isAopIntroduced
     * @return void
     */
    public function setIsAopIntroduced($isAopIntroduced)
    {
        $this->isAopIntroduced = $isAopIntroduced;
    }

    /**
     * @return boolean
     */
    public function isAopIntroduced()
    {
        return $this->isAopIntroduced;
    }
}
