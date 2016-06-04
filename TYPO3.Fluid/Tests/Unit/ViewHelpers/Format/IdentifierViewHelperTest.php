<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers\Format;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

use TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for \TYPO3\Fluid\ViewHelpers\Format\IdentifierViewHelper
 */
class IdentifierViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\Fluid\ViewHelpers\Format\IdentifierViewHelper
     */
    protected $viewHelper;

    /**
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     */
    protected $mockPersistenceManager;

    /**
     * Sets up this test case
     */
    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Format\IdentifierViewHelper', array('renderChildren'));
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->mockPersistenceManager = $this->createMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');
        $this->viewHelper->_set('persistenceManager', $this->mockPersistenceManager);
    }

    /**
     * @test
     */
    public function renderGetsIdentifierForObjectFromPersistenceManager()
    {
        $object = new \stdClass();
        $this->mockPersistenceManager
            ->expects($this->atLeastOnce())
            ->method('getIdentifierByObject')
            ->with($object)
            ->will($this->returnValue('6f487e40-4483-11de-8a39-0800200c9a66'));

        $expectedResult = '6f487e40-4483-11de-8a39-0800200c9a66';
        $actualResult = $this->viewHelper->render($object);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderWithoutValueInvokesRenderChildren()
    {
        $object = new \stdClass();
        $this->viewHelper
            ->expects($this->once())
            ->method('renderChildren')
            ->will($this->returnValue($object));

        $this->mockPersistenceManager
            ->expects($this->once())
            ->method('getIdentifierByObject')
            ->with($object)
            ->will($this->returnValue('b59292c5-1a28-4b36-8615-10d3c5b3a4d8'));

        $this->assertEquals('b59292c5-1a28-4b36-8615-10d3c5b3a4d8', $this->viewHelper->render());
    }

    /**
     * @test
     */
    public function renderReturnsNullIfGivenValueIsNull()
    {
        $this->viewHelper
            ->expects($this->once())
            ->method('renderChildren')
            ->will($this->returnValue(null));

        $this->assertEquals(null, $this->viewHelper->render());
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionIfGivenValueIsNoObject()
    {
        $notAnObject = array();
        $this->viewHelper->render($notAnObject);
    }
}
