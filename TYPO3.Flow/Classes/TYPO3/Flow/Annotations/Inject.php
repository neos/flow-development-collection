<?php
namespace TYPO3\Flow\Annotations;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Used to enable property injection.
 *
 * Flow will build Dependency Injection code for the property and try
 * to inject a value as specified by the var annotation.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Inject
{
    /**
     * Whether the dependency should be injected instantly or if a lazy dependency
     * proxy should be injected instead
     *
     * @var boolean
     */
    public $lazy = true;

    /**
     * Path of a setting (without the package key) which should be injected into the property.
     * Example: session.name
     *
     * @var string
     * @deprecated since 3.0. Use the InjectConfiguration annotation instead.
     */
    public $setting;

    /**
     * Defines the package to be used for retrieving a setting specified via the "setting" parameter. If no package
     * is specified, we'll assume the package to be the same which contains the class where the Inject annotation is
     * used.
     *
     * Example: TYPO3.Flow
     *
     * @var string
     * @deprecated since 3.0. Use the InjectConfiguration annotation instead.
     */
    public $package;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (isset($values['lazy'])) {
            $this->lazy = (boolean)$values['lazy'];
        }
        if (isset($values['setting'])) {
            $this->setting = (string)$values['setting'];
        }
        if (isset($values['package'])) {
            $this->package = (string)$values['package'];
        }
    }
}
