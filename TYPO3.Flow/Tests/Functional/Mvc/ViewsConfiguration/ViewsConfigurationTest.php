<?php
namespace TYPO3\Flow\Tests\Functional\Mvc\ViewsConfiguration;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Client\Browser;
use TYPO3\Flow\Mvc\Routing\Route;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;

/**
 * Functional tests for the ActionController
 */
class ViewsConfigurationTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableHttpEnabled = TRUE;

	/**
	 * @var boolean
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * Additional setup: Routes
	 */
	public function setUp() {
		parent::setUp();

		$this->registerRoute('viewsconfigurationa', 'test/mvc/viewsconfigurationa(/{@action})', array(
			'@package' => 'TYPO3.Flow',
			'@subpackage' => 'Tests\Functional\Mvc\ViewsConfiguration\Fixtures',
			'@controller' => 'ViewsConfigurationTestA',
			'@format' => 'html'
		));

		$this->registerRoute('viewsconfigurationb', 'test/mvc/viewsconfigurationb(/{@action})', array(
			'@package' => 'TYPO3.Flow',
			'@subpackage' => 'Tests\Functional\Mvc\ViewsConfiguration\Fixtures',
			'@controller' => 'ViewsConfigurationTestB',
			'@action' => 'first',
			'@format' => 'html'
		));

		$this->registerRoute('viewsconfigurationc', 'test/mvc/viewsconfigurationc(/{@action})', array(
			'@package' => 'TYPO3.Flow',
			'@subpackage' => 'Tests\Functional\Mvc\ViewsConfiguration\Fixtures',
			'@controller' => 'ViewsConfigurationTestC',
			'@action' => 'index',
			'@format' => 'html'
		));
	}

	/**
	 *
	 *
	 * @test
	 */
	public function templatePathAndFilenameIsChanged() {
		$response = $this->browser->request('http://localhost/test/mvc/viewsconfigurationa/first');
		$this->assertEquals('Changed on Package Level', $response->getContent());
		$response = $this->browser->request('http://localhost/test/mvc/viewsconfigurationb/first');
		$this->assertEquals('Changed on Controller Level', $response->getContent());
	}

	/**
	 *
	 *
	 * @test
	 */
	public function viewObjectNameChanged() {
		$response = $this->browser->request('http://localhost/test/mvc/viewsconfigurationc/index');
		$this->assertEquals('TYPO3\Flow\Tests\Functional\Mvc\ViewsConfiguration\Fixtures\TemplateView', $response->getContent());
	}

	/**
	 * @test
	 */
	public function changeTemplatePathAndFilenameForWidget() {
		$response = $this->browser->request('http://localhost/test/mvc/viewsconfigurationa/widget');
		$this->assertEquals('Changed on Package Level', $response->getContent());
	}

}
?>