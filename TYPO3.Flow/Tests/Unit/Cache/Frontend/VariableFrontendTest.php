<?php
namespace TYPO3\Flow\Tests\Unit\Cache\Frontend;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the variable cache frontend
 *
 */
class VariableFrontendTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @test
     */
    public function setChecksIfTheIdentifierIsValid()
    {
        $cache = $this->getMock(\TYPO3\Flow\Cache\Frontend\StringFrontend::class, array('isValidEntryIdentifier'), array(), '', false);
        $cache->expects($this->once())->method('isValidEntryIdentifier')->with('foo')->will($this->returnValue(false));
        $cache->set('foo', 'bar');
    }

    /**
     * @test
     */
    public function setPassesSerializedStringToBackend()
    {
        $theString = 'Just some value';
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);
        $backend->expects($this->once())->method('set')->with($this->equalTo('VariableCacheTest'), $this->equalTo(serialize($theString)));

        $cache = new \TYPO3\Flow\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theString);
    }

    /**
     * @test
     */
    public function setPassesSerializedArrayToBackend()
    {
        $theArray = array('Just some value', 'and another one.');
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);
        $backend->expects($this->once())->method('set')->with($this->equalTo('VariableCacheTest'), $this->equalTo(serialize($theArray)));

        $cache = new \TYPO3\Flow\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theArray);
    }

    /**
     * @test
     */
    public function setPassesLifetimeToBackend()
    {
        $theString = 'Just some value';
        $theLifetime = 1234;
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);
        $backend->expects($this->once())->method('set')->with($this->equalTo('VariableCacheTest'), $this->equalTo(serialize($theString)), $this->equalTo(array()), $this->equalTo($theLifetime));

        $cache = new \TYPO3\Flow\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $cache->set('VariableCacheTest', $theString, array(), $theLifetime);
    }

    /**
     * @test
     * @requires extension igbinary
     */
    public function setUsesIgBinarySerializeIfAvailable()
    {
        $theString = 'Just some value';
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);
        $backend->expects($this->once())->method('set')->with($this->equalTo('VariableCacheTest'), $this->equalTo(igbinary_serialize($theString)));

        $cache = new \TYPO3\Flow\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $cache->initializeObject();
        $cache->set('VariableCacheTest', $theString);
    }

    /**
     * @test
     */
    public function getFetchesStringValueFromBackend()
    {
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);
        $backend->expects($this->once())->method('get')->will($this->returnValue(serialize('Just some value')));

        $cache = new \TYPO3\Flow\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $this->assertEquals('Just some value', $cache->get('VariableCacheTest'), 'The returned value was not the expected string.');
    }

    /**
     * @test
     */
    public function getFetchesArrayValueFromBackend()
    {
        $theArray = array('Just some value', 'and another one.');
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);
        $backend->expects($this->once())->method('get')->will($this->returnValue(serialize($theArray)));

        $cache = new \TYPO3\Flow\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $this->assertEquals($theArray, $cache->get('VariableCacheTest'), 'The returned value was not the expected unserialized array.');
    }

    /**
     * @test
     */
    public function getFetchesFalseBooleanValueFromBackend()
    {
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);
        $backend->expects($this->once())->method('get')->will($this->returnValue(serialize(false)));

        $cache = new \TYPO3\Flow\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $this->assertFalse($cache->get('VariableCacheTest'), 'The returned value was not the FALSE.');
    }

    /**
     * @test
     * @requires extension igbinary
     */
    public function getUsesIgBinaryIfAvailable()
    {
        $theArray = array('Just some value', 'and another one.');
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);
        $backend->expects($this->once())->method('get')->will($this->returnValue(igbinary_serialize($theArray)));

        $cache = new \TYPO3\Flow\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $cache->initializeObject();

        $this->assertEquals($theArray, $cache->get('VariableCacheTest'), 'The returned value was not the expected unserialized array.');
    }

    /**
     * @test
     */
    public function hasReturnsResultFromBackend()
    {
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);
        $backend->expects($this->once())->method('has')->with($this->equalTo('VariableCacheTest'))->will($this->returnValue(true));

        $cache = new \TYPO3\Flow\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $this->assertTrue($cache->has('VariableCacheTest'), 'has() did not return TRUE.');
    }

    /**
     * @test
     */
    public function removeCallsBackend()
    {
        $cacheIdentifier = 'someCacheIdentifier';
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);

        $backend->expects($this->once())->method('remove')->with($this->equalTo($cacheIdentifier))->will($this->returnValue(true));

        $cache = new \TYPO3\Flow\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $this->assertTrue($cache->remove($cacheIdentifier), 'remove() did not return TRUE');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function getByTagRejectsInvalidTags()
    {
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\BackendInterface::class, array(), array(), '', false);
        $backend->expects($this->never())->method('getByTag');

        $cache = new \TYPO3\Flow\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $cache->getByTag('SomeInvalid\Tag');
    }

    /**
     * @test
     */
    public function getByTagCallsBackendAndReturnsIdentifiersAndValuesOfEntries()
    {
        $tag = 'sometag';
        $identifiers = array('one', 'two');
        $entries = array('one' => 'one value', 'two' => 'two value');
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);

        $backend->expects($this->once())->method('findIdentifiersByTag')->with($this->equalTo($tag))->will($this->returnValue($identifiers));
        $backend->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls(serialize('one value'), serialize('two value')));

        $cache = new \TYPO3\Flow\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $this->assertEquals($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
    }

    /**
     * @test
     * @requires extension igbinary
     */
    public function getByTagUsesIgBinaryIfAvailable()
    {
        $tag = 'sometag';
        $identifiers = array('one', 'two');
        $entries = array('one' => 'one value', 'two' => 'two value');
        $backend = $this->getMock(\TYPO3\Flow\Cache\Backend\AbstractBackend::class, array('get', 'set', 'has', 'remove', 'findIdentifiersByTag', 'flush', 'flushByTag', 'collectGarbage'), array(), '', false);

        $backend->expects($this->once())->method('findIdentifiersByTag')->with($this->equalTo($tag))->will($this->returnValue($identifiers));
        $backend->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls(igbinary_serialize('one value'), igbinary_serialize('two value')));

        $cache = new \TYPO3\Flow\Cache\Frontend\VariableFrontend('VariableFrontend', $backend);
        $cache->initializeObject();

        $this->assertEquals($entries, $cache->getByTag($tag), 'Did not receive the expected entries');
    }
}
