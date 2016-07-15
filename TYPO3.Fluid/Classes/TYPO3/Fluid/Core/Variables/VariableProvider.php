<?php
namespace TYPO3\Fluid\Core\Variables;

use TYPO3\Flow\Reflection\Exception\PropertyNotAccessibleException;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Fluid\Core\Parser\SyntaxTree\TemplateObjectAccessInterface;
use TYPO3Fluid\Fluid\Core\Variables\Exception;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;
use TYPO3Fluid\Fluid\Core\Variables\VariableProviderInterface;

/**
 *
 */
class VariableProvider extends StandardVariableProvider implements VariableProviderInterface
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
