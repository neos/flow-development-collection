<?php
namespace TYPO3\Fluid\Tests\Unit\Core\ViewHelper;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for TagBuilder
 */
class TagBuilderTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function constructorSetsTagName()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('someTagName');
        $this->assertEquals('someTagName', $tagBuilder->getTagName());
    }

    /**
     * @test
     */
    public function constructorSetsTagContent()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('', '<some text>');
        $this->assertEquals('<some text>', $tagBuilder->getContent());
    }

    /**
     * @test
     */
    public function setContentDoesNotEscapeValue()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder();
        $tagBuilder->setContent('<to be escaped>', false);
        $this->assertEquals('<to be escaped>', $tagBuilder->getContent());
    }

    /**
     * @test
     */
    public function hasContentReturnsTrueIfTagContainsText()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('', 'foo');
        $this->assertTrue($tagBuilder->hasContent());
    }

    /**
     * @test
     */
    public function hasContentReturnsFalseIfContentIsNull()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder();
        $tagBuilder->setContent(null);
        $this->assertFalse($tagBuilder->hasContent());
    }

    /**
     * @test
     */
    public function hasContentReturnsFalseIfContentIsAnEmptyString()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder();
        $tagBuilder->setContent('');
        $this->assertFalse($tagBuilder->hasContent());
    }

    /**
     * @test
     */
    public function renderReturnsEmptyStringByDefault()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder();
        $this->assertEquals('', $tagBuilder->render());
    }

    /**
     * @test
     */
    public function renderReturnsSelfClosingTagIfNoContentIsSpecified()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('tag');
        $this->assertEquals('<tag />', $tagBuilder->render());
    }

    /**
     * @test
     */
    public function contentCanBeRemoved()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('tag', 'some content');
        $tagBuilder->setContent(null);
        $this->assertEquals('<tag />', $tagBuilder->render());
    }

    /**
     * @test
     */
    public function renderReturnsOpeningAndClosingTagIfNoContentIsSpecifiedButForceClosingTagIsTrue()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('tag');
        $tagBuilder->forceClosingTag(true);
        $this->assertEquals('<tag></tag>', $tagBuilder->render());
    }

    /**
     * @test
     */
    public function attributesAreProperlyRendered()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('tag');
        $tagBuilder->addAttribute('attribute1', 'attribute1value');
        $tagBuilder->addAttribute('attribute2', 'attribute2value');
        $tagBuilder->addAttribute('attribute3', 'attribute3value');
        $this->assertEquals('<tag attribute1="attribute1value" attribute2="attribute2value" attribute3="attribute3value" />', $tagBuilder->render());
    }

    /**
     * @test
     */
    public function attributeValuesAreEscapedByDefault()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('tag');
        $tagBuilder->addAttribute('foo', '<to be escaped>');
        $this->assertEquals('<tag foo="&lt;to be escaped&gt;" />', $tagBuilder->render());
    }

    /**
     * @test
     */
    public function attributeValuesAreNotEscapedIfDisabled()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('tag');
        $tagBuilder->addAttribute('foo', '<not to be escaped>', false);
        $this->assertEquals('<tag foo="<not to be escaped>" />', $tagBuilder->render());
    }

    /**
     * @test
     */
    public function attributesCanBeRemoved()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('tag');
        $tagBuilder->addAttribute('attribute1', 'attribute1value');
        $tagBuilder->addAttribute('attribute2', 'attribute2value');
        $tagBuilder->addAttribute('attribute3', 'attribute3value');
        $tagBuilder->removeAttribute('attribute2');
        $this->assertEquals('<tag attribute1="attribute1value" attribute3="attribute3value" />', $tagBuilder->render());
    }

    /**
     * @test
     */
    public function attributesCanBeAccessed()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('tag');
        $tagBuilder->addAttribute('attribute1', 'attribute1value');
        $attributeValue = $tagBuilder->getAttribute('attribute1');
        $this->assertSame('attribute1value', $attributeValue);
    }

    /**
     * @test
     */
    public function getAttributeWithMissingAttributeReturnsNull()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('tag');
        $attributeValue = $tagBuilder->getAttribute('missingattribute');
        $this->assertNull($attributeValue);
    }

    /**
     * @test
     */
    public function resetResetsTagBuilder()
    {
        $tagBuilder = $this->getAccessibleMock(\TYPO3\Fluid\Core\ViewHelper\TagBuilder::class, array('dummy'));
        $tagBuilder->setTagName('tagName');
        $tagBuilder->setContent('some content');
        $tagBuilder->forceClosingTag(true);
        $tagBuilder->addAttribute('attribute1', 'attribute1value');
        $tagBuilder->addAttribute('attribute2', 'attribute2value');
        $tagBuilder->reset();

        $this->assertEquals('', $tagBuilder->_get('tagName'));
        $this->assertEquals('', $tagBuilder->_get('content'));
        $this->assertEquals(array(), $tagBuilder->_get('attributes'));
        $this->assertFalse($tagBuilder->_get('forceClosingTag'));
    }

    /**
     * @test
     */
    public function tagNameCanBeOverridden()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('foo');
        $tagBuilder->setTagName('bar');
        $this->assertEquals('<bar />', $tagBuilder->render());
    }

    /**
     * @test
     */
    public function tagContentCanBeOverridden()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('foo', 'some content');
        $tagBuilder->setContent('');
        $this->assertEquals('<foo />', $tagBuilder->render());
    }

    /**
     * @test
     */
    public function tagIsNotRenderedIfTagNameIsEmpty()
    {
        $tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('foo');
        $tagBuilder->setTagName('');
        $this->assertEquals('', $tagBuilder->render());
    }
}
