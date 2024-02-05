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
     *
     * @param string $path
     * @return mixed
     */
    public function getByPath($path)
    {
        $subject = parent::getByPath($path);

        if ($subject === null) {
            $subject = $this->getBooleanValue($path);
        }

        if ($subject instanceof TemplateObjectAccessInterface) {
            return $subject->objectAccess();
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
     *
     * @param $path
     * @return boolean|null
     */
    protected function getBooleanValue($path)
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
