<?php
namespace TYPO3\Flow\Mvc\View;

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
 * An abstract View
 *
 * @api
 */
abstract class AbstractView implements ViewInterface {

	/**
	 * This contains the supported options, their default values, descriptions and types.
	 * Syntax example:
	 *     array(
	 *         'someOptionName' => array('defaultValue', 'some description', 'string'),
	 *         'someOtherOptionName' => array('defaultValue', some description', integer),
	 *         ...
	 *     )
	 *
	 * @var array
	 */
	protected $supportedOptions = array();

	/**
	 * The configuration options of this view
	 * @see $supportedOptions
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * View variables and their values
	 * @var array
	 * @see assign()
	 */
	protected $variables = array();

	/**
	 * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * Set default options based on the supportedOptions provided
	 *
	 * @param array $options
	 * @throws \TYPO3\Flow\Mvc\Exception
	 */
	public function __construct(array $options = array()) {
			// check for options given but not supported
		if (($unsupportedOptions = array_diff_key($options, $this->supportedOptions)) !== array()) {
			throw new \TYPO3\Flow\Mvc\Exception(sprintf('The view options "%s" you\'re trying to set don\'t exist in class "%s".', implode(',', array_keys($unsupportedOptions)), get_class($this)), 1359625876);
		}

			// check for required options being set
		array_walk(
			$this->supportedOptions,
			function($supportedOptionData, $supportedOptionName, $options) {
				if (isset($supportedOptionData[3]) && !array_key_exists($supportedOptionName, $options)) {
					throw new \TYPO3\Flow\Mvc\Exception('Required view option not set: ' . $supportedOptionName, 1359625876);
				}
			},
			$options
		);

			// merge with default values
		$this->options = array_merge(
			array_map(
				function ($value) {
					return $value[0];
				},
				$this->supportedOptions
			),
			$options
		);
	}

	/**
	 * Get a specific option of this View
	 *
	 * @param string $optionName
	 * @return mixed
	 */
	public function getOption($optionName) {
		if (!array_key_exists($optionName, $this->supportedOptions)) {
			throw new \TYPO3\Flow\Mvc\Exception(sprintf('The view option "%s" you\'re trying to get doesn\'t exist in class "%s".', $optionName, get_class($this)), 1359625876);
		}

		return $this->options[$optionName];
	}

	/**
	 * Set a specific option of this View
	 *
	 * @param string $optionName
	 * @param mixed $value
	 * @return void
	 */
	public function setOption($optionName, $value) {
		if (!array_key_exists($optionName, $this->supportedOptions)) {
			throw new \TYPO3\Flow\Mvc\Exception(sprintf('The view option "%s" you\'re trying to set doesn\'t exist in class "%s".', $optionName, get_class($this)), 1359625876);
		}

		$this->options[$optionName] = $value;
	}

	/**
	 * Add a variable to $this->variables.
	 * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible
	 *
	 * @param string $key Key of variable
	 * @param mixed $value Value of object
	 * @return \TYPO3\Flow\Mvc\View\AbstractView an instance of $this, to enable chaining
	 * @api
	 */
	public function assign($key, $value) {
		$this->variables[$key] = $value;
		return $this;
	}

	/**
	 * Add multiple variables to $this->variables.
	 *
	 * @param array $values array in the format array(key1 => value1, key2 => value2)
	 * @return \TYPO3\Flow\Mvc\View\AbstractView an instance of $this, to enable chaining
	 * @api
	 */
	public function assignMultiple(array $values) {
		foreach ($values as $key => $value) {
			$this->assign($key, $value);
		}
		return $this;
	}

	/**
	 * Sets the current controller context
	 *
	 * @param \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext
	 * @return void
	 * @api
	 */
	public function setControllerContext(\TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	/**
	 * Tells if the view implementation can render the view for the given context.
	 *
	 * By default we assume that the view implementation can handle all kinds of
	 * contexts. Override this method if that is not the case.
	 *
	 * @param \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext
	 * @return boolean TRUE if the view has something useful to display, otherwise FALSE
	 */
	public function canRender(\TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext) {
		return TRUE;
	}

}
