<?php
namespace TYPO3\Fluid\ViewHelpers\Validation;

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
use TYPO3\Flow\Error\Result;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * This view helper allows to check whether validation errors adhere to the current request.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <f:validation.ifHasErrors>
 *   <div class="alert">Please fill out all fields according to the description</div>
 * </f:validation.ifHasErrors>
 * </code>
 *
 * <code title="Usage with property path in forms">
 * <f:form name="blog">
 *   <div class="row {f:validation.ifHasErrors(for: 'blog.title', then: 'has-error')}">
 *     <f:form.textfield property="title" />
 *     <span class="error-text">You must provide a title.</span>
 *   </div>
 * </f:form>
 * </code>
 *
 * @api
 */
class IfHasErrorsViewHelper extends AbstractConditionViewHelper
{
    /**
     * Renders <f:then> child if there are validation errors. The check can be narrowed down to
     * specific property paths.
     * If no errors are there, it renders the <f:else>-child.
     *
     * @param string $for The argument or property name or path to check for error(s)
     * @return string
     * @api
     */
    public function render($for = null)
    {
        /** @var $request ActionRequest */
        $request = $this->controllerContext->getRequest();
        /** @var $validationResults Result */
        $validationResults = $request->getInternalArgument('__submittedArgumentValidationResults');

        if ($validationResults !== null) {
            // if $for is not set, ->forProperty will return the initial Result object untouched
            $validationResults = $validationResults->forProperty($for);
            if ($validationResults->hasErrors()) {
                return $this->renderThenChild();
            }
        }
        return $this->renderElseChild();
    }
}
