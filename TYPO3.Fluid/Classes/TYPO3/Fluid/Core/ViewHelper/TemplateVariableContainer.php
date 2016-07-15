<?php
namespace TYPO3\Fluid\Core\ViewHelper;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Reflection\Exception\PropertyNotAccessibleException;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Fluid\Core\Parser\SyntaxTree\TemplateObjectAccessInterface;
use TYPO3Fluid\Fluid\Core\Variables\Exception;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;
use TYPO3Fluid\Fluid\Core\Variables\VariableProviderInterface;

/**
 * Provides the variables inside fluid template. Adds TemplateObjectAccessInterface functionality.
 *
 * @api
 */
class TemplateVariableContainer extends StandardVariableProvider implements VariableProviderInterface
{
    /**
     * Get a variable by dotted path expression, retrieving the
     * variable from nested arrays/objects one segment at a time.
     * If the second argument is provided, it must be an array of
     * accessor names which can be used to extract each value in
     * the dotted path.
     *
     * @param string $path
     * @param array $accessors
     * @return mixed
     */
    public function getByPath($path, array $accessors = [])
    {
        $subject = $this->variables;

        $propertyPathSegments = explode('.', $path);
        foreach ($propertyPathSegments as $propertyName) {
            try {
                $subject = ObjectAccess::getProperty($subject, $propertyName);
            } catch (PropertyNotAccessibleException $exception) {
                $subject = null;
            }

            if ($subject === null) {
                break;
            }

            if ($subject instanceof TemplateObjectAccessInterface) {
                $subject = $subject->objectAccess();
            }
        }

        return $subject;
    }
}
