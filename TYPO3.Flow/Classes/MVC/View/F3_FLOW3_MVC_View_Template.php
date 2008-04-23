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
 * @subpackage MVC
 * @version $Id$
 */

/**
 * A basic Template View
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_MVC_View_Template extends F3_FLOW3_MVC_View_Abstract {

	/**
	 * @var string
	 */
	protected $templateResource;

	/**
	 * @var array Marker identifiers and their replacement content
	 */
	protected $markers = array();

	/**
	 * @var array Parts
	 */
	protected $parts = array();

	/**
	 * Sets the text resource which contains the markers this template view
	 * is going to fill in.
	 *
	 * As long as we don't have a Resource Framework, this method just accepts
	 * a string.
	 *
	 * @param string $template The template
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Adapt as soon as we have a Resource Management
	 */
	public function setTemplateResource($templateResource) {
		$this->templateResource = $templateResource;
	}

	/**
	 * Sets the content of a marker. All markers with this name will be
	 * replaced by the content when this template is rendered.
	 *
	 * @param string $marker The marker which will be replaced by $content
	 * @param string $content The fill-in for the specified marker
	 * @return void
	 * @throws F3_FLOW3_MVC_Exception_InvalidMarker if the marker is not a valid string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setMarkerContent($marker, $content) {
		if (!is_string($marker)) throw new F3_FLOW3_MVC_Exception_InvalidMarker('A template marker must be a valid string, ' . gettype($marker) . ' given.', 1187334295);
		$this->markers[$marker] = $content;
	}

	/**
	 * Sets the content of a part. All parts which are enclosed by markers
	 * with this name will be replaced by the content when this template
	 * is rendered.
	 *
	 * @param string $partMarker Marker which identifies the part
	 * @param string $content The fill-in for the specified part
	 * @return void
	 * @throws F3_FLOW3_MVC_Exception_InvalidPart if the part marker is not a valid string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPartContent($partMarker, $content) {
		if (!is_string($partMarker)) throw new F3_FLOW3_MVC_Exception_InvalidPart('A template part marker must be a valid string, ' . gettype($partMarker) . ' given.', 1187334296);
		$this->parts[$partMarker] = $content;
	}

	/**
	 * Renders this template view.
	 *
	 * @return string The rendered template view
	 * @throws F3_FLOW3_MVC_Exception_InvalidTemplateResource if no template resource has been defined yet
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		if ($this->templateResource == '') throw new F3_FLOW3_MVC_Exception_InvalidTemplateResource('No template resource has been defined yet.', 1187860750);
		$output = $this->templateResource;
		foreach ($this->markers as $marker => $content) {
			$output = str_replace('###' . F3_PHP6_Functions::strtoupper($marker) . '###', $content, $output);
		}

		foreach($this->parts as $marker => $content) {
			$output = preg_replace('/<!--\s*###' . F3_PHP6_Functions::strtoupper(preg_quote($marker, '/')) . '###.*###' . F3_PHP6_Functions::strtoupper(preg_quote($marker, '/')) . '###.*-->/msU', $content, $output);
		}
		return $output;
	}

	/**
	 * Substitutes a subpart in $content with the content of $subpartContent.
	 *
	 * @param string Content with subpart wrapped in fx. "###CONTENT_PART###" inside.
	 * @param string Marker string, eg. "###CONTENT_PART###"
	 * @param array
	 * @return string Processed input content
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function substitutePart($subject, $marker, $replacement) {
	}
}
?>