<?php
declare(ENCODING = 'utf-8');

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
 * Bootstrap for the FLOW3 Framework
 *
 * @version $Id$
 * @author Robert Lemke <robert@typo3.org>
 * @author Karsten Dambekalns <karsten@typo3.org>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */

version_compare(phpversion(), '5.3.0', '>=') or die('Because FLOW3 uses namespaces, it requires at least PHP 5.3.0, you have ' . phpversion() . ' (Error #<a href="http://typo3.org/go/exception/1246258365">1246258365</a>)' . PHP_EOL);
require(__DIR__ . '/../Classes/Core/Bootstrap.php');

	// Need to take this detour because PHP < 5.3.0 would die with a parse error, not displaying our message above
$className = '\F3\FLOW3\Core\Bootstrap';
$className::defineConstants();

$flow3 = new $className(getenv('FLOW3_CONTEXT'));
$flow3->initialize();
$flow3->run();

?>