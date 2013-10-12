<?php
namespace TYPO3\Flow\Tests\Functional\Mvc\ViewsConfiguration\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * An empty view - a special case.
 *
 */
final class TemplateView extends \TYPO3\Flow\Mvc\View\AbstractView {
	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'templateRootPathPattern' => array('@packageResourcesPath/Private/Templates', 'Pattern to be resolved for "@templateRoot" in the other patterns', 'string'),
		'partialRootPathPattern' => array('@packageResourcesPath/Private/Partials', 'Pattern to be resolved for "@partialRoot" in the other patterns', 'string'),
		'layoutRootPathPattern' => array('@packageResourcesPath/Private/Layouts', 'Pattern to be resolved for "@layoutRoot" in the other patterns', 'string'),

		'templateRootPath' => array(NULL, 'Path to the template root. If NULL, then $this->templateRootPathPattern will be used', 'string'),
		'partialRootPath' => array(NULL, 'Path to the partial root. If NULL, then $this->partialRootPathPattern will be used', 'string'),
		'layoutRootPath' => array(NULL, 'Path to the layout root. If NULL, then $this->layoutRootPathPattern will be used', 'string'),

		'templatePathAndFilenamePattern' => array('@templateRoot/@subpackage/@controller/@action.@format', 'File pattern for resolving the template file', 'string'),
		'partialPathAndFilenamePattern' => array('@partialRoot/@subpackage/@partial.@format', 'Directory pattern for global partials. Not part of the public API, should not be changed for now.', 'string'),
		'layoutPathAndFilenamePattern' => array('@layoutRoot/@layout.@format', 'File pattern for resolving the layout', 'string'),

		'templatePathAndFilename' => array(NULL, 'Path and filename of the template file. If set,  overrides the templatePathAndFilenamePattern', 'string'),
		'layoutPathAndFilename' => array(NULL, 'Path and filename of the layout file. If set, overrides the layoutPathAndFilenamePattern', 'string'),
	);

	/**
	 * Dummy method to satisfy the ViewInterface
	 *
	 * @param \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext
	 * @return void
	 * @api
	 */
	public function setControllerContext(\TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext) {
	}

	/**
	 * Dummy method to satisfy the ViewInterface
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return \TYPO3\Flow\Mvc\View\EmptyView instance of $this to allow chaining
	 * @api
	 */
	public function assign($key, $value) {
		return $this;
	}

	/**
	 * Dummy method to satisfy the ViewInterface
	 *
	 * @param array $values
	 * @return \TYPO3\Flow\Mvc\View\EmptyView instance of $this to allow chaining
	 * @api
	 */
	public function assignMultiple(array $values) {
		return $this;
	}

	/**
	 * This view can be used in any case.
	 *
	 * @param \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext
	 * @return boolean TRUE
	 * @api
	 */
	public function canRender(\TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext) {
		return TRUE;
	}

	/**
	 * Renders the empty view
	 *
	 * @return string An empty string
	 */
	public function render() {
		return get_class($this);
	}

	/**
	 * A magic call method.
	 *
	 * Because this empty view is used as a Special Case in situations when no matching
	 * view is available, it must be able to handle method calls which originally were
	 * directed to another type of view. This magic method should prevent PHP from issuing
	 * a fatal error.
	 *
	 * @param string $methodName Name of the method
	 * @param array $arguments Arguments passed to the method
	 * @return void
	 */
	public function __call($methodName, array $arguments) {
	}
}
