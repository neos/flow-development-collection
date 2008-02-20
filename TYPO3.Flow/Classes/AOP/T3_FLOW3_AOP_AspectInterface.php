<?php
declare(ENCODING = 'utf-8');

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
 * @subpackage AOP
 * @version $Id: $
 */

/**
 * An aspect is a modularized form of cross-cutting concerns to prevent the
 * violation of the separation of concerns. More concretely a TYPO3 Aspect
 * class is a container for advisors which represent single advices which
 * can be applied to join points.
 *
 * In TYPO3 aspects are encapsulated in a normal PHP class which is tagged
 * by the "aspect" annotation. This marker interface must be implemented by
 * any aspect.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:T3_FLOW3_AOP_AspectInterface.php 201 2007-03-30 11:18:30Z robert $
 * @author Robert Lemke <robert@typo3.org>
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface T3_FLOW3_AOP_AspectInterface {

}

?>