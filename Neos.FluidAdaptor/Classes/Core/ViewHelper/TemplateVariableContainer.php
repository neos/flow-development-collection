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

use Neos\Utility\Exception\PropertyNotAccessibleException;
use Neos\Utility\ObjectAccess;
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
     * @param string $identifier
     * @return mixed
     */
    public function get($identifier)
    {
        $subject = parent::get($identifier);
        if ($subject instanceof TemplateObjectAccessInterface) {
            $subject = $subject->objectAccess();
        }
        return $subject;
    }

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
        $propertyPathSegments = explode('.', $path);
        $subject = $this->variables;

        foreach ($propertyPathSegments as $propertyName) {
            if ($subject === null) {
                break;
            }

            try {
                $subject = ObjectAccess::getProperty($subject, $propertyName);
            } catch (PropertyNotAccessibleException $exception) {
                $subject = null;
            }

            if ($subject instanceof TemplateObjectAccessInterface) {
                $subject = $subject->objectAccess();
            }
        }

        if ($subject === null) {
            $subject = $this->getBooleanValue($path);
        }

        return $subject;
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
