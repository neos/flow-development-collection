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
 * @subpackage Locale
 * @version $Id$
 */

/**
 * A Service which provides further information about a given locale.
 *
 * @package FLOW3
 * @subpackage Locale
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Locale_Service {

	/**
	 * @var F3_FLOW3_Component_FactoryInterface
	 */
	protected $componentFactory;

	/**
	 * @var F3_FLOW3_Configuration_Container
	 */
	protected $configuration;

	/**
	 * Constructs this service
	 *
	 * @param F3_FLOW3_Configuration_Container $settings The FLOW3 settings
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Configuration_Container $settings) {
		$this->settings = $settings;
	}

	/**
	 * Injects the component factory
	 *
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory A reference to the component factory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectComponentFactory(F3_FLOW3_Component_FactoryInterface $componentFactory) {
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Initializes this locale service
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize() {
		$locale = $this->componentFactory->getComponent('F3_FLOW3_Locale_Locale', $this->settings->locale->defaultLocaleIdentifier);
		$this->settings->locale->defaultLocale = $locale;
	}

}

?>