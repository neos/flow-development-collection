<?php

declare(strict_types=1);

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

use Neos\Flow\Tests\FunctionalTestCase;
use Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Domain\Model\Post;
use Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Domain\Model\Tag;
use Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Domain\Model\User;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DomCrawler\Field\InputFormField;

/**
 * Testcase for Standalone View
 */
#[Large]
final class FormObjectsTest extends FunctionalTestCase
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
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerRoute(
            'Form Test Route',
            'test/fluid/formobjects(/{@action})',
            [
                '@package' => 'Neos.FluidAdaptor',
                '@subpackage' => 'Tests\Functional\Form\Fixtures',
                '@controller' => 'Form',
                '@action' => 'index',
                '@format' => 'html'
            ],
            true
        );
    }

    #[Test]
    public function objectIsCreatedCorrectly()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects');
        $form = $this->browser->getForm();

        $form['post']['name']->setValue('Neos Team');
        $form['post']['author']['emailAddress']->setValue('hello@neos.io');

        $response = $this->browser->submit($form);
        self::assertSame('Neos Team|hello@neos.io', $response->getBody()->getContents());
    }

    #[Test]
    public function multipleCheckboxRendersCorrectFieldNameForEntities()
    {
        $postIdentifier = $this->setupDummyPost(true);

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();
        self::assertArrayNotHasKey('__identity', $form['post']['tags'], 'Post tags identities not set.');
        self::assertArrayNotHasKey('__identity', $form['tags'], 'Tags identities not set.');
    }

    #[Test]
    public function embeddedValueObjectWillNotRenderHiddenIdentityField()
    {
        $postIdentifier = $this->setupDummyPost(true);

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();
        self::assertArrayNotHasKey('__identity', $form['post']['author']['location']);
    }

    #[Test]
    public function formIsRedisplayedIfValidationErrorsOccur()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects');
        $form = $this->browser->getForm();

        $form['post']['name']->setValue('Neos Team');
        $form['post']['author']['emailAddress']->setValue('test_noValidEmail');

        $this->browser->submit($form);
        $form = $this->browser->getForm();
        self::assertSame('Neos Team', $form['post']['name']->getValue());
        self::assertSame('test_noValidEmail', $form['post']['author']['emailAddress']->getValue());
        self::assertSame('f3-form-error', $this->browser->getCrawler()->filterXPath('//*[@id="email"]')->attr('class'));

        $form['post']['author']['emailAddress']->setValue('another@email.org');

        $response = $this->browser->submit($form);
        self::assertSame('Neos Team|another@email.org', $response->getBody()->getContents());
    }

    #[Test]
    public function formForPersistedObjectIsRedisplayedIfValidationErrorsOccur()
    {
        $postIdentifier = $this->setupDummyPost();

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();

        $form['post']['name']->setValue('Egon Olsen');
        $form['post']['author']['emailAddress']->setValue('test_noValidEmail');

        $this->browser->submit($form);
        $form = $this->browser->getForm();
        self::assertSame('Egon Olsen', $form['post']['name']->getValue());
        self::assertSame('test_noValidEmail', $form['post']['author']['emailAddress']->getValue());
        self::assertSame('f3-form-error', $this->browser->getCrawler()->filterXPath('//*[@id="email"]')->attr('class'));

        $form['post']['author']['emailAddress']->setValue('another@email.org');

        $response = $this->browser->submit($form);
        self::assertSame('Egon Olsen|another@email.org', $response->getBody()->getContents());
    }

    #[Test]
    public function formErrorsForNonObjectAccessorFieldsAreHighlightedIfValidationErrorsOccur()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects/check');
        $form = $this->browser->getForm();

        $form['email']->setValue('test_noValidEmail');

        $this->browser->submit($form);

        $form = $this->browser->getForm();
        self::assertSame('f3-form-error', $this->browser->getCrawler()->filterXPath('//*[@id="email"]')->attr('class'));
    }

    #[Test]
    public function valueOfNonObjectAccessorFieldsIsOverriddenBySubmittedValueIfValidationErrorsOccur()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects/check');
        $form = $this->browser->getForm();

        $form['email']->setValue('test_noValidEmail');

        $this->browser->submit($form);

        $form = $this->browser->getForm();
        self::assertSame('test_noValidEmail', $form['email']->getValue());
    }

    #[Test]
    public function objectIsNotCreatedAnymoreIfHmacHasBeenTampered()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects');
        $form = $this->browser->getForm();

        $form['__trustedProperties']->setValue($form['__trustedProperties']->getValue() . 'a');
        $this->browser->submit($form);

        self::assertSame(400, $this->browser->getLastResponse()->getStatusCode());
    }

    #[Test]
    public function objectIsNotCreatedAnymoreIfIdentityFieldHasBeenAdded()
    {
        $postIdentifier = $this->setupDummyPost();
        $this->browser->request('http://localhost/test/fluid/formobjects');
        $form = $this->browser->getForm();

        $identityFieldDom = dom_import_simplexml(simplexml_load_string('<input type="text" name="post[__identity]" value="' . $postIdentifier . '" />'));
        $form->set(new InputFormField($identityFieldDom));

        $this->browser->submit($form);

        self::assertSame(500, $this->browser->getLastResponse()->getStatusCode());
    }

    #[Test]
    public function objectIsNotCreatedAnymoreIfNewFieldHasBeenAdded()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects');
        $form = $this->browser->getForm();

        $identityFieldDom = dom_import_simplexml(simplexml_load_string('<input type="text" name="post[someProperty]" value="someValue" />'));
        $form->set(new InputFormField($identityFieldDom));

        $this->browser->submit($form);

        self::assertSame(500, $this->browser->getLastResponse()->getStatusCode());
    }

    #[Test]
    public function objectIsNotCreatedAnymoreIfHmacIsRemoved()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects');
        $form = $this->browser->getForm();

        unset($form['__trustedProperties']);
        $this->browser->submit($form);

        self::assertSame(500, $this->browser->getLastResponse()->getStatusCode());
    }

    #[Test]
    public function objectIsNotModifiedOnFormError()
    {
        $postIdentifier = $this->setupDummyPost();

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();
        $form['post']['name']->setValue('Hello World');
        $form['post']['author']['emailAddress']->setValue('test_noValidEmail');

        $response = $this->browser->submit($form);
        self::assertNotSame('Hello World|test_noValidEmail', $response->getBody()->getContents());

        $this->persistenceManager->clearState();
        $post = $this->persistenceManager->getObjectByIdentifier($postIdentifier, Post::class);
        self::assertNotSame('test_noValidEmail', $post->getAuthor()->getEmailAddress(), 'The invalid email address "' . $post->getAuthor()->getEmailAddress() . '" was persisted!');
    }

    #[Test]
    public function objectCanBeModifiedAfterFormError()
    {
        $postIdentifier = $this->setupDummyPost();

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();
        $form['post']['name']->setValue('Hello World');
        $form['post']['author']['emailAddress']->setValue('test_noValidEmail');

        $this->browser->submit($form);

        self::assertSame($postIdentifier, $this->browser->getCrawler()->filterXPath('//input[@name="post[__identity]"]')->attr('value'));

        $form['post']['name']->setValue('Hello World');
        $form['post']['author']['emailAddress']->setValue('foo@bar.org');
        $response = $this->browser->submit($form);
        self::assertSame('Hello World|foo@bar.org', $response->getBody()->getContents());

        $post = $this->persistenceManager->getObjectByIdentifier($postIdentifier, Post::class);
        self::assertSame('foo@bar.org', $post->getAuthor()->getEmailAddress());
    }

    #[Test]
    public function objectCanBeModified()
    {
        $postIdentifier = $this->setupDummyPost();

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();

        self::assertSame('myName', $form['post']['name']->getValue());

        $form['post']['name']->setValue('Hello World');
        $response = $this->browser->submit($form);
        self::assertSame('Hello World|foo@bar.org', $response->getBody()->getContents());
    }

    #[Test]
    public function objectIsNotModifiedAnymoreIfHmacHasBeenManipulated()
    {
        $postIdentifier = $this->setupDummyPost();

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();

        $form['__trustedProperties']->setValue($form['__trustedProperties']->getValue() . 'a');
        $this->browser->submit($form);

        self::assertSame(400, $this->browser->getLastResponse()->getStatusCode());
    }

    #[Test]
    public function objectIsNotModifiedAnymoreIfIdentityFieldHasBeenRemoved()
    {
        $postIdentifier = $this->setupDummyPost();

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();
        $form->remove('post[__identity]');

        $this->browser->submit($form);

        self::assertSame(500, $this->browser->getLastResponse()->getStatusCode());
    }

    #[Test]
    public function objectIsNotModifiedAnymoreIfNewFieldHasBeenAdded()
    {
        $postIdentifier = $this->setupDummyPost();

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();

        $privateFieldDom = dom_import_simplexml(simplexml_load_string('<input type="text" name="post[pivate]" value="0" />'));
        $form->set(new InputFormField($privateFieldDom));

        $this->browser->submit($form);

        self::assertSame(500, $this->browser->getLastResponse()->getStatusCode());
    }

    #[Test]
    public function objectIsNotModifiedAnymoreIfHmacIsRemoved()
    {
        $postIdentifier = $this->setupDummyPost();

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $form = $this->browser->getForm();

        unset($form['__trustedProperties']);
        $this->browser->submit($form);

        self::assertSame(500, $this->browser->getLastResponse()->getStatusCode());
    }

    /**
     * @param boolean $withTags
     * @return string UUID of the dummy post
     */
    protected function setupDummyPost($withTags = false)
    {
        $author = new User();
        $author->setEmailAddress('foo@bar.org');
        $post = new Post();
        $post->setAuthor($author);
        $post->setName('myName');
        $post->setPrivate(true);
        if ($withTags === true) {
            $post->addTag(new Tag('Tag1'));
            $post->addTag(new Tag('Tag2'));
        }
        $this->persistenceManager->add($post);
        $postIdentifier = $this->persistenceManager->getIdentifierByObject($post);
        $this->persistenceManager->persistAll();
        $this->persistenceManager->clearState();

        return $postIdentifier;
    }

    #[Test]
    public function checkboxIsCheckedCorrectlyOnValidationErrorsEvenIfDefaultTrueValue()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects');
        $form = $this->browser->getForm();

        $form['post']['author']['emailAddress']->setValue('test_noValidEmail');
        $form['post']['private']->setValue(false);

        $this->browser->submit($form);
        self::assertEmpty($this->browser->getCrawler()->filterXPath('//input[@id="private"]')->attr('checked'));

        $form['post']['private']->setValue(true);
        $this->browser->submit($form);
        self::assertNotNull($this->browser->getCrawler()->filterXPath('//input[@id="private"]')->attr('checked'));
    }

    #[Test]
    public function radioButtonsAreCheckedCorrectlyOnValidationErrors()
    {
        $this->browser->request('http://localhost/test/fluid/formobjects');
        $form = $this->browser->getForm();

        $form['post']['author']['emailAddress']->setValue('test_noValidEmail');
        $form['post']['category']->setValue('bar');
        $form['post']['subCategory']->setValue('bar');

        $this->browser->submit($form);

        self::assertNull($this->browser->getCrawler()->filterXPath('//input[@id="category_foo"]')->attr('checked'));
        self::assertNotNull($this->browser->getCrawler()->filterXPath('//input[@id="category_bar"]')->attr('checked'));
        self::assertNull($this->browser->getCrawler()->filterXPath('//input[@id="subCategory_foo"]')->attr('checked'));
        self::assertNotNull($this->browser->getCrawler()->filterXPath('//input[@id="subCategory_bar"]')->attr('checked'));

        $form['post']['category']->setValue('foo');
        $form['post']['subCategory']->setValue('foo');

        $this->browser->submit($form);

        self::assertNotNull($this->browser->getCrawler()->filterXPath('//input[@id="category_foo"]')->attr('checked'));
        self::assertNull($this->browser->getCrawler()->filterXPath('//input[@id="category_bar"]')->attr('checked'));
        self::assertNotNull($this->browser->getCrawler()->filterXPath('//input[@id="subCategory_foo"]')->attr('checked'));
        self::assertNull($this->browser->getCrawler()->filterXPath('//input[@id="subCategory_bar"]')->attr('checked'));
    }

    #[Test]
    public function valueForDisabledCheckboxIsNotLost()
    {
        $postIdentifier = $this->setupDummyPost();
        $post = $this->persistenceManager->getObjectByIdentifier($postIdentifier, Post::class);
        self::assertEquals(true, $post->getPrivate());

        $this->browser->request('http://localhost/test/fluid/formobjects/edit?fooPost=' . $postIdentifier);
        $checkboxDisabled = $this->browser->getCrawler()->filterXPath('//*[@id="private"]')->attr('disabled');
        self::assertNotNull($checkboxDisabled, 'Private checkbox was not disabled.');
        self::assertEquals($checkboxDisabled, $this->browser->getCrawler()->filterXPath('//input[@type="hidden" and contains(@name,"private")]')->attr('disabled'), 'The hidden checkbox field is not disabled like the connected checkbox.');

        $form = $this->browser->getForm();
        $this->browser->submit($form);

        $this->persistenceManager->clearState();
        $post = $this->persistenceManager->getObjectByIdentifier($postIdentifier, Post::class);
        // This will currently never fail, because DomCrawler\Form does not handle hidden checkbox fields correctly!
        // Hence this test currently only relies on the correctly set "disabled" attribute on the hidden field.
        self::assertEquals(true, $post->getPrivate(), 'The value for the checkbox field "private" was lost on form submit!');
    }
}
