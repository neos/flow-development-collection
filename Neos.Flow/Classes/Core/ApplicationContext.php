<?php
namespace Neos\Flow\Core;

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
use Neos\Flow\Exception as FlowException;

/**
 * The Flow Context object.
 *
 * A Flow Application context is something like "Production", "Development",
 * "Production/StagingSystem", and is set using the FLOW_CONTEXT environment variable.
 *
 * A context can contain arbitrary sub-contexts, which are delimited with slash
 * ("Production/StagingSystem", "Production/Staging/Server1"). The top-level
 * contexts, however, must be one of "Testing", "Development" and "Production".
 *
 * Mainly, you will use $context->isProduction(), $context->isTesting() and
 * $context->isDevelopment() inside your custom code.
 *
 * @api
 * @Flow\Proxy(false)
 */
class ApplicationContext
{
    /**
     * The (internal) context string; could be something like "Development" or "Development/MyLocalMacBook"
     *
     * @var string
     */
    protected $contextString;

    /**
     * The root context; must be one of "Development", "Testing" or "Production"
     *
     * @var string
     */
    protected $rootContextString;

    /**
     * The parent context, or NULL if there is no parent context
     *
     * @var ApplicationContext
     */
    protected $parentContext;

    /**
     * @var string[]
     */
    protected $hierarchy = [];

    /**
     * Initialize the context object.
     *
     * @param string $contextString
     * @throws FlowException if the parent context is none of "Development", "Production" or "Testing"
     */
    public function __construct(string $contextString)
    {
        $contextStringParts = explode('/', $contextString);
        $this->rootContextString = reset($contextStringParts);

        $combinedContextStrings = '';
        foreach ($contextStringParts as $contextStringPart) {
            $this->hierarchy[] = $combinedContextStrings . $contextStringPart;
            $combinedContextStrings .= $contextStringPart . '/';
        }

        array_pop($contextStringParts);
        if ($contextStringParts !== []) {
            $this->parentContext = new ApplicationContext(implode('/', $contextStringParts));
        }

        if (!in_array($this->rootContextString, ['Development', 'Production', 'Testing'])) {
            throw new FlowException('The given context "' . $contextString . '" was not valid. Only allowed are Development, Production and Testing, including their sub-contexts', 1335436551);
        }
        $this->contextString = $contextString;
    }

    /**
     * Returns the full context string, for example "Development", or "Production/LiveSystem"
     *
     * @return string
     * @api
     */
    public function __toString(): string
    {
        return $this->contextString;
    }

    /**
     * Returns true if this context is the Development context or a sub-context of it
     *
     * @return boolean
     * @api
     */
    public function isDevelopment(): bool
    {
        return ($this->rootContextString === 'Development');
    }

    /**
     * Returns true if this context is the Production context or a sub-context of it
     *
     * @return boolean
     * @api
     */

    public function isProduction(): bool
    {
        return ($this->rootContextString === 'Production');
    }

    /**
     * Returns true if this context is the Testing context or a sub-context of it
     *
     * @return boolean
     * @api
     */
    public function isTesting(): bool
    {
        return ($this->rootContextString === 'Testing');
    }

    /**
     * Returns the parent context object, if any
     *
     * @return ApplicationContext the parent context or NULL, if there is none
     * @api
     */
    public function getParent()
    {
        return $this->parentContext;
    }

    /**
     * Returns the names of this context and all parents in ascending specificity
     *
     * @return string[] for example ['Production', 'Production/Staging', 'Production/Staging/Server1']
     * @api
     */
    public function getHierarchy(): array
    {
        return $this->hierarchy;
    }
}
