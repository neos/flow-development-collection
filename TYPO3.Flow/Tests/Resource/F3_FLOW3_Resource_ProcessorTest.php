<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Resource;

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
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\Object\ClassLoaderTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the resource processor
 *
 * @package FLOW3
 * @version $Id:\F3\FLOW3\Object\ClassLoaderTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ProcessorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function canAdjustRelativePathsInHTML() {
		$originalHTML = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<base href="###BASEURI###" />
		<style type="text/css">
			.F3_WidgetLibrary_Widgets_FloatingWindow {
				background-image: url(DefaultView_FloatingWindow.png);
			}
		</style>
		<link rel="stylesheet" href="SomeCoolStyle.css" />
	</head>
	<body>
		<img src="DefaultView_Package.png" class="DefaultView_Package" />
		<a href="http://test.invalid/">do not change this link</a>
		<a href="/an/absolute/URL/">nor this link</a>
		<a href="#samePage">nor that link</a>
	</body>
</html>';
		$expectedHTML = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<base href="###BASEURI###" />
		<style type="text/css">
			.F3_WidgetLibrary_Widgets_FloatingWindow {
				background-image: url(test/prefix/to/insert/DefaultView_FloatingWindow.png);
			}
		</style>
		<link rel="stylesheet" href="test/prefix/to/insert/SomeCoolStyle.css" />
	</head>
	<body>
		<img src="test/prefix/to/insert/DefaultView_Package.png" class="DefaultView_Package" />
		<a href="http://test.invalid/">do not change this link</a>
		<a href="/an/absolute/URL/">nor this link</a>
		<a href="#samePage">nor that link</a>
	</body>
</html>';
		$processor = new \F3\FLOW3\Resource\Processor();
		$processedHTML = $processor->prefixRelativePathsInHTML($originalHTML, 'test/prefix/to/insert/');
		$this->assertEquals($processedHTML, $expectedHTML, 'The processed HTML was not changed as expected.');
	}
}

?>