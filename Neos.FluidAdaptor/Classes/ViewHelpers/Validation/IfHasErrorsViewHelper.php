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


use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Result;
use Neos\Flow\Mvc\ActionRequest;
use Neos\FluidAdaptor\Core\Rendering\FlowAwareRenderingContextInterface;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractConditionViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

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
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('then', 'mixed', 'Value to be returned if the condition if met.', false);
        $this->registerArgument('else', 'mixed', 'Value to be returned if the condition if not met.', false);
        $this->registerArgument('for', 'string', 'The argument or property name or path to check for error(s). If not set any validation error leads to the "then child" to be rendered', false);
    }

    /**
     * Renders <f:then> child if there are validation errors. The check can be narrowed down to
     * specific property paths.
     * If no errors are there, it renders the <f:else>-child.
     *
     * @return mixed
     * @api
     */
    public function render()
    {
        if (self::evaluateCondition($this->arguments, $this->renderingContext)) {
            return $this->renderThenChild();
        } else {
            return $this->renderElseChild();
        }
    }

    /**
     * @param null $arguments
     * @param FlowAwareRenderingContextInterface|RenderingContextInterface $renderingContext
     * @return boolean
     */
    protected static function evaluateCondition($arguments = null, RenderingContextInterface $renderingContext)
    {

        /** @var $request ActionRequest */
        $request = $renderingContext->getControllerContext()->getRequest();
        /** @var $validationResults Result */
        $validationResults = $request->getInternalArgument('__submittedArgumentValidationResults');

        if ($validationResults === null) {
            return false;
        }
        if (isset($arguments['for'])) {
            $validationResults = $validationResults->forProperty($arguments['for']);
        }
        return $validationResults->hasErrors();
    }
}
