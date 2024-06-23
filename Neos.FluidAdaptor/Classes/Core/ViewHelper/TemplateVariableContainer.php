<?php

namespace Neos\FluidAdaptor\Core\ViewHelper;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\Parser\SyntaxTree\TemplateObjectAccessInterface;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;

/**
 * Provides the variables inside fluid template. Adds TemplateObjectAccessInterface functionality.
 *
 * @api
 */
class TemplateVariableContainer extends StandardVariableProvider
{
    /**
     * Get a variable by dotted path expression, retrieving the
     * variable from nested arrays/objects one segment at a time.
     *
     * This sadly mostly copies the parent method to add handling for
     * subjects of type TemplateObjectAccessInterface.
     *
     * @param string $path
     * @return mixed
     */
    public function getByPath($path)
    {
        // begin copy of parent method
        $subject = $this->variables;
        $subVariableReferences = explode('.', $this->resolveSubVariableReferences($path));
        foreach ($subVariableReferences as $pathSegment) {
            // begin TemplateObjectAccessInterface handling
            if ($subject instanceof TemplateObjectAccessInterface) {
                $subject = $subject->objectAccess();
            }
            // end TemplateObjectAccessInterface handling
            if ((is_array($subject) && array_key_exists($pathSegment, $subject))
                || ($subject instanceof \ArrayAccess && $subject->offsetExists($pathSegment))
            ) {
                $subject = $subject[$pathSegment];
                continue;
            }
            if (is_object($subject)) {
                $upperCasePropertyName = ucfirst($pathSegment);
                $getMethod = 'get' . $upperCasePropertyName;
                if (method_exists($subject, $getMethod)) {
                    $subject = $subject->$getMethod();
                    continue;
                }
                $isMethod = 'is' . $upperCasePropertyName;
                if (method_exists($subject, $isMethod)) {
                    $subject = $subject->$isMethod();
                    continue;
                }
                $hasMethod = 'has' . $upperCasePropertyName;
                if (method_exists($subject, $hasMethod)) {
                    $subject = $subject->$hasMethod();
                    continue;
                }
                if (property_exists($subject, $pathSegment)) {
                    $subject = $subject->$pathSegment;
                    continue;
                }
            }
            // begin TemplateObjectAccessInterface handling
            $subject = null;
            break;
            // end TemplateObjectAccessInterface handling
        }
        // end copy of parent method

        if ($subject === null) {
            $subject = $this->getBooleanValue($path);
        }

        // we might still have a TemplateObjectAccessInterface instance
        if ($subject instanceof TemplateObjectAccessInterface) {
            $subject = $subject->objectAccess();
        }

        return $subject;
    }

    /**
     * @param string $propertyPath
     * @return string
     */
    protected function resolveSubVariableReferences(string $propertyPath): string
    {
        if (strpos($propertyPath, '{') !== false) {
            // NOTE: This is an inclusion of https://github.com/TYPO3/Fluid/pull/472 to allow multiple nested variables
            preg_match_all('/(\{.*?\})/', $propertyPath, $matches);
            foreach ($matches[1] as $match) {
                $subPropertyPath = substr($match, 1, -1);
                $propertyPath = str_replace($match, $this->getByPath($subPropertyPath), $propertyPath);
            }
        }
        return $propertyPath;
    }

    /**
     * Tries to interpret the given path as boolean value, either returns the boolean value or null.
     */
    protected function getBooleanValue(string $path): ?bool
    {
        $normalizedPath = strtolower($path);

        if (in_array($normalizedPath, ['true', 'on', 'yes'])) {
            return true;
        }

        if (in_array($normalizedPath, ['false', 'off', 'no'])) {
            return false;
        }

        return null;
    }
}
