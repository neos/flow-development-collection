<?php
namespace Neos\FluidAdaptor\ViewHelpers\Validation;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Result;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * Validation results view helper
 *
 * = Examples =
 *
 * <code title="Output error messages as a list">
 * <f:validation.results>
 *   <f:if condition="{validationResults.flattenedErrors}">
 *     <ul class="errors">
 *       <f:for each="{validationResults.flattenedErrors}" as="errors" key="propertyPath">
 *         <li>{propertyPath}
 *           <ul>
 *           <f:for each="{errors}" as="error">
 *             <li>{error.code}: {error}</li>
 *           </f:for>
 *           </ul>
 *         </li>
 *       </f:for>
 *     </ul>
 *   </f:if>
 * </f:validation.results>
 * </code>
 * <output>
 * <ul class="errors">
 *   <li>1234567890: Validation errors for argument "newBlog"</li>
 * </ul>
 * </output>
 *
 * <code title="Output error messages for a single property">
 * <f:validation.results for="someProperty">
 *   <f:if condition="{validationResults.flattenedErrors}">
 *     <ul class="errors">
 *       <f:for each="{validationResults.errors}" as="error">
 *         <li>{error.code}: {error}</li>
 *       </f:for>
 *     </ul>
 *   </f:if>
 * </f:validation.results>
 * </code>
 * <output>
 * <ul class="errors">
 *   <li>1234567890: Some error message</li>
 * </ul>
 * </output>
 *
 * @api
 */
class ResultsViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * Arguments initialization
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('for', 'string', 'The name of the error name (e.g. argument name or property name). This can also be a property path (like blog.title), and will then only display the validation errors of that property.', false, '');
        $this->registerArgument('as', 'string', 'The name of the variable to store the current error', false, 'validationResults');
    }

    /**
     * Iterates through selected errors of the request.
     *
     * @return string Rendered string
     * @api
     */
    public function render()
    {
        $for = $this->arguments['for'];
        $as = $this->arguments['as'];

        $request = $this->controllerContext->getRequest();
        /** @var $validationResults Result */
        $validationResults = $request->getInternalArgument('__submittedArgumentValidationResults');
        if ($validationResults instanceof Result && $for !== '') {
            $validationResults = $validationResults->forProperty($for);
        }
        $this->templateVariableContainer->add($as, $validationResults);
        $output = $this->renderChildren();
        $this->templateVariableContainer->remove($as);

        return $output;
    }
}
