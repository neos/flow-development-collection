<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Event;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Event
 * @version $Id$
 */

/**
 * Event base class
 *
 * @package FLOW3
 * @subpackage Event
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Event {
	
	/**
	 * The event type.
	 * This should be a string-based constant which resides in the respective Event subclass.
	 *
	 * @var string Event type
	 */
	protected $type;

	/**
	 * Constructor
	 *
	 * @param string $type Event type
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function __construct($type) {
		$this->type = $type;
	}

	/**
	 * Returns the type of this Event.
	 *
	 * @return string The event type
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getType() {
		return $this->type;
	}
}
?>