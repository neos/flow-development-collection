<?php
namespace Neos\FluidAdaptor\Tests\Functional\Form;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for Standalone View
 *
 * @group large
 */
class FormObjectsTest extends \Neos\Flow\Tests\FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var \Neos\Flow\Http\Client\Browser
     */
    protected $browser;

    /**
     * Initializer
     */
    public function setUp()
    {
        parent::setUp();

        $route = new \Neos\Flow\Mvc\Routing\Route();
        $route->setUriPattern('test/fluid/formobjects(/{@action})');
        $route->setDefaults(array(
            '@package' => 'Neos.FluidAdaptor',
            '@subpackage' => 'Tests\Functional\Form\Fixtures',
            '@controller' => 'Form',
            '@action' => 'index',
            '@format' => 'html'
        ));
        $route->setAppendExceedingArguments(true);
        $this->router->addRoute($route);
    }

    /**
     * @test
     */
    public function objectIsCreatedCorrectly()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects');
        $form = $this->browser->getForm();

        $form['post']['name']->setValue('Neos Team');
        $form['post']['author']['emailAddress']->setValue('hello@neos.io');

        $response = $this->browser->submit($form);
        $this->assertSame('Neos Team|hello@neos.io', $response->getContent());
    }

    /**
     * @test
     */
    public function multipleCheckboxRendersCorrectFieldNameForEntities()
    {
        $postIdentifier = $this->setupDummyPost(true);

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();
        $this->assertFalse(isset($form['post']['tags']['__identity']));
        $this->assertFalse(isset($form['tags']['__identity']));
    }

    /**
     * @test
     */
    public function embeddedValueObjectWillNotRenderHiddenIdentityField()
    {
        $postIdentifier = $this->setupDummyPost(true);

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();
        $this->assertFalse(isset($form['post']['author']['location']['__identity']));
    }

    /**
     * @test
     */
    public function formIsRedisplayedIfValidationErrorsOccur()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects');
        $form = $this->browser->getForm();

        $form['post']['name']->setValue('Neos Team');
        $form['post']['author']['emailAddress']->setValue('test_noValidEmail');

        $this->browser->submit($form);
        $form = $this->browser->getForm();
        $this->assertSame('Neos Team', $form['post']['name']->getValue());
        $this->assertSame('test_noValidEmail', $form['post']['author']['emailAddress']->getValue());
        $this->assertSame('f3-form-error', $this->browser->getCrawler()->filterXPath('//*[@id="email"]')->attr('class'));

        $form['post']['author']['emailAddress']->setValue('another@email.org');

        $response = $this->browser->submit($form);
        $this->assertSame('Neos Team|another@email.org', $response->getContent());
    }

    /**
     * @test
     */
    public function formForPersistedObjectIsRedisplayedIfValidationErrorsOccur()
    {
        $postIdentifier = $this->setupDummyPost();

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();

        $form['post']['name']->setValue('Egon Olsen');
        $form['post']['author']['emailAddress']->setValue('test_noValidEmail');

        $this->browser->submit($form);
        $form = $this->browser->getForm();
        $this->assertSame('Egon Olsen', $form['post']['name']->getValue());
        $this->assertSame('test_noValidEmail', $form['post']['author']['emailAddress']->getValue());
        $this->assertSame('f3-form-error', $this->browser->getCrawler()->filterXPath('//*[@id="email"]')->attr('class'));

        $form['post']['author']['emailAddress']->setValue('another@email.org');

        $response = $this->browser->submit($form);
        $this->assertSame('Egon Olsen|another@email.org', $response->getContent());
    }

    /**
     * @test
     */
    public function formErrorsForNonObjectAccessorFieldsAreHighlightedIfValidationErrorsOccur()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects/check');
        $form = $this->browser->getForm();

        $form['email']->setValue('test_noValidEmail');

        $this->browser->submit($form);

        $form = $this->browser->getForm();
        $this->assertSame('f3-form-error', $this->browser->getCrawler()->filterXPath('//*[@id="email"]')->attr('class'));
    }

    /**
     * @test
     */
    public function valueOfNonObjectAccessorFieldsIsOverriddenBySubmittedValueIfValidationErrorsOccur()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects/check');
        $form = $this->browser->getForm();

        $form['email']->setValue('test_noValidEmail');

        $this->browser->submit($form);

        $form = $this->browser->getForm();
        $this->assertSame('test_noValidEmail', $form['email']->getValue());
    }

    /**
     * @test
     */
    public function objectIsNotCreatedAnymoreIfHmacHasBeenTampered()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects');
        $form = $this->browser->getForm();

        $form['__trustedProperties']->setValue($form['__trustedProperties']->getValue() . 'a');
        $this->browser->submit($form);

        $this->assertSame('500 Internal Server Error', $this->browser->getLastResponse()->getStatus());
    }

    /**
     * @test
     */
    public function objectIsNotCreatedAnymoreIfIdentityFieldHasBeenAdded()
    {
        $postIdentifier = $this->setupDummyPost();
        $this->browser->request('http://localhost/test/fluid/formobjects');
        $form = $this->browser->getForm();

        $identityFieldDom = dom_import_simplexml(simplexml_load_string('<input type="text" name="post[__identity]" value="' . $postIdentifier . '" />'));
        $form->set(new \Symfony\Component\DomCrawler\Field\InputFormField($identityFieldDom));

        $this->browser->submit($form);

        $this->assertSame('500 Internal Server Error', $this->browser->getLastResponse()->getStatus());
    }

    /**
     * @test
     */
    public function objectIsNotCreatedAnymoreIfNewFieldHasBeenAdded()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects');
        $form = $this->browser->getForm();

        $identityFieldDom = dom_import_simplexml(simplexml_load_string('<input type="text" name="post[someProperty]" value="someValue" />'));
        $form->set(new \Symfony\Component\DomCrawler\Field\InputFormField($identityFieldDom));

        $this->browser->submit($form);

        $this->assertSame('500 Internal Server Error', $this->browser->getLastResponse()->getStatus());
    }

    /**
     * @test
     */
    public function objectIsNotCreatedAnymoreIfHmacIsRemoved()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects');
        $form = $this->browser->getForm();

        unset($form['__trustedProperties']);
        $this->browser->submit($form);

        $this->assertSame('500 Internal Server Error', $this->browser->getLastResponse()->getStatus());
    }

    /**
     * @test
     */
    public function objectIsNotModifiedOnFormError()
    {
        $postIdentifier = $this->setupDummyPost();

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();
        $form['post']['name']->setValue('Hello World');
        $form['post']['author']['emailAddress']->setValue('test_noValidEmail');

        $response = $this->browser->submit($form);
        $this->assertNotSame('Hello World|test_noValidEmail', $response->getContent());

        $this->persistenceManager->clearState();
        $post = $this->persistenceManager->getObjectByIdentifier($postIdentifier, \Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Domain\Model\Post::class);
        $this->assertNotSame('test_noValidEmail', $post->getAuthor()->getEmailAddress(), 'The invalid email address "' . $post->getAuthor()->getEmailAddress() . '" was persisted!');
    }

    /**
     * @test
     */
    public function objectCanBeModifiedAfterFormError()
    {
        $postIdentifier = $this->setupDummyPost();

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();
        $form['post']['name']->setValue('Hello World');
        $form['post']['author']['emailAddress']->setValue('test_noValidEmail');

        $this->browser->submit($form);

        $this->assertSame($postIdentifier, $this->browser->getCrawler()->filterXPath('//input[@name="post[__identity]"]')->attr('value'));

        $form['post']['name']->setValue('Hello World');
        $form['post']['author']['emailAddress']->setValue('foo@bar.org');
        $response = $this->browser->submit($form);
        $this->assertSame('Hello World|foo@bar.org', $response->getContent());

        $post = $this->persistenceManager->getObjectByIdentifier($postIdentifier, \Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Domain\Model\Post::class);
        $this->assertSame('foo@bar.org', $post->getAuthor()->getEmailAddress());
    }

    /**
     * @test
     */
    public function objectCanBeModified()
    {
        $postIdentifier = $this->setupDummyPost();

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();

        $this->assertSame('myName', $form['post']['name']->getValue());

        $form['post']['name']->setValue('Hello World');
        $response = $this->browser->submit($form);
        $this->assertSame('Hello World|foo@bar.org', $response->getContent());
    }

    /**
     * @test
     */
    public function objectIsNotModifiedAnymoreIfHmacHasBeenManipulated()
    {
        $postIdentifier = $this->setupDummyPost();

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();

        $form['__trustedProperties']->setValue($form['__trustedProperties']->getValue() . 'a');
        $this->browser->submit($form);

        $this->assertSame('500 Internal Server Error', $this->browser->getLastResponse()->getStatus());
    }

    /**
     * @test
     */
    public function objectIsNotModifiedAnymoreIfIdentityFieldHasBeenRemoved()
    {
        $postIdentifier = $this->setupDummyPost();

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();
        $form->remove('post[__identity]');

        $this->browser->submit($form);

        $this->assertSame('500 Internal Server Error', $this->browser->getLastResponse()->getStatus());
    }

    /**
     * @test
     */
    public function objectIsNotModifiedAnymoreIfNewFieldHasBeenAdded()
    {
        $postIdentifier = $this->setupDummyPost();

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();

        $privateFieldDom = dom_import_simplexml(simplexml_load_string('<input type="text" name="post[pivate]" value="0" />'));
        $form->set(new \Symfony\Component\DomCrawler\Field\InputFormField($privateFieldDom));

        $this->browser->submit($form);

        $this->assertSame('500 Internal Server Error', $this->browser->getLastResponse()->getStatus());
    }

    /**
     * @test
     */
    public function objectIsNotModifiedAnymoreIfHmacIsRemoved()
    {
        $postIdentifier = $this->setupDummyPost();

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();

        unset($form['__trustedProperties']);
        $this->browser->submit($form);

        $this->assertSame('500 Internal Server Error', $this->browser->getLastResponse()->getStatus());
    }

    /**
     * @param boolean $withTags
     * @return string UUID of the dummy post
     */
    protected function setupDummyPost($withTags = false)
    {
        $author = new Fixtures\Domain\Model\User();
        $author->setEmailAddress('foo@bar.org');
        $post = new Fixtures\Domain\Model\Post();
        $post->setAuthor($author);
        $post->setName('myName');
        $post->setPrivate(true);
        if ($withTags === true) {
            $post->addTag(new Fixtures\Domain\Model\Tag('Tag1'));
            $post->addTag(new Fixtures\Domain\Model\Tag('Tag2'));
        }
        $this->persistenceManager->add($post);
        $postIdentifier = $this->persistenceManager->getIdentifierByObject($post);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        return $postIdentifier;
    }

    /**
     * @test
     */
    public function checkboxIsCheckedCorrectlyOnValidationErrorsEvenIfDefaultTrueValue()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects');
        $form = $this->browser->getForm();

        $form['post']['author']['emailAddress']->setValue('test_noValidEmail');
        $form['post']['private']->setValue(false);

        $this->browser->submit($form);
        $this->assertEmpty($this->browser->getCrawler()->filterXPath('//input[@id="private"]')->attr('checked'));

        $form['post']['private']->setValue(true);
        $this->browser->submit($form);
        $this->assertSame('checked', $this->browser->getCrawler()->filterXPath('//input[@id="private"]')->attr('checked'));
    }

    /**
     * @test
     */
    public function radioButtonsAreCheckedCorrectlyOnValidationErrors()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects');
        $form = $this->browser->getForm();

        $form['post']['author']['emailAddress']->setValue('test_noValidEmail');
        $form['post']['category']->setValue('bar');
        $form['post']['subCategory']->setValue('bar');

        $this->browser->submit($form);

        $this->assertEquals('', $this->browser->getCrawler()->filterXPath('//input[@id="category_foo"]')->attr('checked'));
        $this->assertEquals('checked', $this->browser->getCrawler()->filterXPath('//input[@id="category_bar"]')->attr('checked'));
        $this->assertEquals('', $this->browser->getCrawler()->filterXPath('//input[@id="subCategory_foo"]')->attr('checked'));
        $this->assertEquals('checked', $this->browser->getCrawler()->filterXPath('//input[@id="subCategory_bar"]')->attr('checked'));

        $form['post']['category']->setValue('foo');
        $form['post']['subCategory']->setValue('foo');

        $this->browser->submit($form);

        $this->assertEquals('checked', $this->browser->getCrawler()->filterXPath('//input[@id="category_foo"]')->attr('checked'));
        $this->assertEquals('', $this->browser->getCrawler()->filterXPath('//input[@id="category_bar"]')->attr('checked'));
        $this->assertEquals('checked', $this->browser->getCrawler()->filterXPath('//input[@id="subCategory_foo"]')->attr('checked'));
        $this->assertEquals('', $this->browser->getCrawler()->filterXPath('//input[@id="subCategory_bar"]')->attr('checked'));
    }

    /**
     * @test
     */
    public function valueForDisabledCheckboxIsNotLost()
    {
        $postIdentifier = $this->setupDummyPost();
        $post = $this->persistenceManager->getObjectByIdentifier($postIdentifier, \Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Domain\Model\Post::class);
        $this->assertEquals(true, $post->getPrivate());

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $checkboxDisabled = $this->browser->getCrawler()->filterXPath('//*[@id="private"]')->attr('disabled');
        $this->assertNotEmpty($checkboxDisabled);
        $this->assertEquals($checkboxDisabled, $this->browser->getCrawler()->filterXPath('//input[@type="hidden" and contains(@name,"private")]')->attr('disabled'), 'The hidden checkbox field is not disabled like the connected checkbox.');

        $form = $this->browser->getForm();
        $this->browser->submit($form);

        $this->persistenceManager->clearState();
        $post = $this->persistenceManager->getObjectByIdentifier($postIdentifier, \Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Domain\Model\Post::class);
        // This will currently never fail, because DomCrawler\Form does not handle hidden checkbox fields correctly!
        // Hence this test currently only relies on the correctly set "disabled" attribute on the hidden field.
        $this->assertEquals(true, $post->getPrivate(), 'The value for the checkbox field "private" was lost on form submit!');
    }
}
