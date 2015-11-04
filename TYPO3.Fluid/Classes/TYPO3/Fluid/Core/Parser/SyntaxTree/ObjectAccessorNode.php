<?php
namespace TYPO3\Fluid\Core\Parser\SyntaxTree;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\Exception\PropertyNotAccessibleException;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * A node which handles object access. This means it handles structures like {object.accessor.bla}
 */
class ObjectAccessorNode extends AbstractNode
{
    /**
     * Object path which will be called. Is a list like "post.name.email"
     * @var string
     */
    protected $objectPath;

    /**
     * Constructor. Takes an object path as input.
     *
     * The first part of the object path has to be a variable in the
     * TemplateVariableContainer.
     *
     * @param string $objectPath An Object Path, like object1.object2.object3
     */
    public function __construct($objectPath)
    {
        $this->objectPath = $objectPath;
    }


    /**
     * Internally used for building up cached templates; do not use directly!
     *
     * @return string
     * @Flow\Internal
     */
    public function getObjectPath()
    {
        return $this->objectPath;
    }

    /**
     * Evaluate this node and return the correct object.
     *
     * Handles each part (denoted by .) in $this->objectPath in the following order:
     * - call appropriate getter
     * - call public property, if exists
     * - fail
     *
     * The first part of the object path has to be a variable in the
     * TemplateVariableContainer.
     *
     * @param RenderingContextInterface $renderingContext
     * @return object The evaluated object, can be any object type.
     */
    public function evaluate(RenderingContextInterface $renderingContext)
    {
        return self::getPropertyPath($renderingContext->getTemplateVariableContainer(), $this->objectPath, $renderingContext);
    }

    /**
     * Gets a property path from a given object or array.
     *
     * If propertyPath is "bla.blubb", then we first call getProperty($object, 'bla'),
     * and on the resulting object we call getProperty(..., 'blubb').
     *
     * For arrays the keys are checked likewise.
     *
     * @param mixed $subject An object or array
     * @param string $propertyPath
     * @param RenderingContextInterface $renderingContext
     * @return mixed Value of the property
     */
    public static function getPropertyPath($subject, $propertyPath, RenderingContextInterface $renderingContext)
    {
        $propertyPathSegments = explode('.', $propertyPath);
        foreach ($propertyPathSegments as $pathSegment) {
            try {
                $subject = ObjectAccess::getProperty($subject, $pathSegment);
            } catch (PropertyNotAccessibleException $exception) {
                $subject = null;
            }

            if ($subject === null) {
                break;
            }

            if ($subject instanceof RenderingContextAwareInterface) {
                $subject->setRenderingContext($renderingContext);
            }
            if ($subject instanceof TemplateObjectAccessInterface) {
                $subject = $subject->objectAccess();
            }
        }
        return $subject;
    }
}
