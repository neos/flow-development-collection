<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Locale;

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
class Service {

	/**
	 * @var F3::FLOW3::Component::FactoryInterface
	 */
	protected $componentFactory;

	/**
	 * @var F3::FLOW3::Configuration::Container
	 */
	protected $configuration;

	/**
	 * Constructs this service
	 *
	 * @param F3::FLOW3::Configuration::Container $settings The FLOW3 settings
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3::FLOW3::Configuration::Container $settings) {
		$this->settings = $settings;
	}

	/**
	 * Injects the component factory
	 *
	 * @param F3::FLOW3::Component::FactoryInterface $componentFactory A reference to the component factory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectComponentFactory(F3::FLOW3::Component::FactoryInterface $componentFactory) {
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Initializes this locale service
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize() {
		$locale = $this->componentFactory->getComponent('F3::FLOW3::Locale::Locale', $this->settings->locale->defaultLocaleIdentifier);
		$this->settings->locale->defaultLocale = $locale;
	}

}

?>