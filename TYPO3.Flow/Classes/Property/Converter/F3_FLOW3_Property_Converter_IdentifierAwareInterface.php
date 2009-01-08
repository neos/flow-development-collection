<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Property\Converter;

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
 * @subpackage Property
 * @version $Id:$
 */
/**
 * If a PropertyConverter implements this interface, it has to expose the
 * identifier of the last converted property.
 * Usually, PropertyConverters which deal with domain objects need to implement
 * this interface.
 */
interface IdentifierAwareInterface {
	/**
	 * Get identifier of the last converted object, if it has one.
	 *
	 * @return string The string representation of the identifier of the last converted object.
	 */
	public function getIdentifier();
}
?>