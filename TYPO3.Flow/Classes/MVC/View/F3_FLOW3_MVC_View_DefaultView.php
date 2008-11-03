<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC::View;

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
 * @subpackage MVC
 * @version $Id:F3::FLOW3::MVC::View::Default.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * The default view - a special case.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3::FLOW3::MVC::View::Default.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class DefaultView extends F3::FLOW3::MVC::View::AbstractView {

	/**
	 * @var F3::FLOW3::MVC::Request
	 */
	protected $request;

	/**
	 * Renders the default view
	 *
	 * @return string The rendered view
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @throws F3::FLOW3::MVC::Exception if no request has been set
	 */
	public function render() {
		if (!is_object($this->request)) throw new F3::FLOW3::MVC::Exception('Can\'t render view without request object.', 1192450280);

		$template = $this->componentFactory->create('F3::FLOW3::MVC::View::Template');
		$template->setTemplateResource($this->resourceManager->getResource('file://FLOW3/Public/MVC/DefaultView_Template.html')->getContent());

		if ($this->request instanceof F3::FLOW3::MVC::Web::Request) {
			$template->setMarkerContent('baseuri', $this->request->getBaseURI());
		}
		return $template->render();
	}
}

?>