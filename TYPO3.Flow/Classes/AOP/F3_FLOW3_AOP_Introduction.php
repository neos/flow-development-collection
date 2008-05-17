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
 * @version $Id$
 */

/**
 * Implementation of the Introduction declaration.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3_FLOW3_AOP_Introduction.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_AOP_Introduction implements F3_FLOW3_AOP_IntroductionInterface {

	/**
	 * @var string Name of the aspect declaring this introduction
	 */
	protected $declaringAspectClassName;

	/**
	 * @var string Name of the introduced interface
	 */
	protected $interfaceName;

	/**
	 * @var F3_FLOW3_AOP_PointcutInterface The poincut this introduction applies to
	 */
	protected $pointcut;

	/**
	 * Constructor
	 *
	 * @param string $declaringAspectClassName: Name of the aspect containing the declaration for this introduction
	 * @param string $interfaceName: Name of the interface to introduce
	 * @param F3_FLOW3_AOP_PointcutInterface $pointcut: The pointcut for this introduction
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($declaringAspectClassName, $interfaceName, F3_FLOW3_AOP_PointcutInterface $pointcut) {
		$this->declaringAspectClassName = $declaringAspectClassName;
		$this->interfaceName = $interfaceName;
		$this->pointcut = $pointcut;
	}

	/**
	 * Returns the name of the introduced interface
	 *
	 * @return string Name of the introduced interface
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getInterfaceName() {
		return $this->interfaceName;
	}

	/**
	 * Returns the poincut this introduction applies to
	 *
	 * @return F3_FLOW3_AOP_PointcutInterface The pointcut
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPointcut() {
		return $this->pointcut;
	}

	/**
	 * Returns the component name of the aspect which declared this introduction
	 *
	 * @return string The aspect component name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDeclaringAspectClassName() {
		return $this->declaringAspectClassName;
	}
}
?>