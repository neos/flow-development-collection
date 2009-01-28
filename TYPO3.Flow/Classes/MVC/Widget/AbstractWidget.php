<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Widget;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * An abstract widget - mother of all widgets
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class AbstractWidget {

	/**
	 * @var \F3\FLOW3\MVC\Widget\AbstractWidget The parent widget. If it is NULL, this widget is a toplevel widget
	 */
	protected $parent = NULL;

	/**
	 * @var string The widget ID, especially used in the XHTML, CSS and JS context
	 */
	protected $id;

	/**
	 * @var array Sub widgets to this widget
	 */
	protected $childWidgets = array();

	/**
	 * Constructs this widget
	 *
	 * @param \F3\FLOW3\MVC\Widget\AbstractWidget $parent A reference to the parent widget, if any
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \InvalidArgumentException if parent was of the wrong type
	 */
	public function __construct($parent = NULL) {
		$this->id = uniqid();
		if (is_object($parent)) {
			if (!$parent instanceof \F3\FLOW3\MVC\Widget\AbstractWidget) throw new \InvalidArgumentException('The specified parent was no instance of \F3\FLOW3\MVC\Widget\AbstractWidget.', 1186730161);
			$parent->addChildWidget($this);
			$this->parent = $parent;
		}
	}

	/**
	 * Sets the reference to the parent widget.
	 *
	 * @param object $parent Reference to the parent widget
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setParent($parent) {
		if (!is_object($parent) || !$parent instanceof \F3\FLOW3\MVC\Widget\AbstractWidget) throw new \InvalidArgumentException('The specified parent was no instance of \F3\FLOW3\MVC\Widget\AbstractWidget.', 1186730280);
		$this->parent = $parent;
	}

	/**
	 * Returns the reference to the parent widget.
	 * If it is NULL, this widget is considered to be a
	 * toplevel widget.
	 *
	 * @return \F3\FLOW3\MVC\Widget\AbstractWidget	Reference to the parent widget or NULL
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 * Returns the identifier of this widget instance
	 *
	 * @return string Identifier of this widget instance
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Adds a child to this widget
	 *
	 * @param \F3\FLOW3\MVC\Widget\AbstractWidget	$childWidget: The child widget to add
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addChildWidget(\F3\FLOW3\MVC\Widget\AbstractWidget $childWidget) {
		$this->childWidgets[] = $childWidget;
		$childWidget->setParent($this);
	}

	/**
	 * Returns an array of all child widgets
	 *
	 * @return array An array of \F3\FLOW3\MVC\Widget\AbstractWidget objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getChildWidgets() {
		return $this->childWidgets;
	}

	/**
	 * Renders the widget and returns the result
	 *
	 * @return string The rendered widget
	 * @author Robert Lemke <robert@typo3.org>
	 */
	abstract public function render();
}
?>