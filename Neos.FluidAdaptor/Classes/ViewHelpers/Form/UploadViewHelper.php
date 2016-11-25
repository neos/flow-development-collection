<?php
namespace Neos\FluidAdaptor\ViewHelpers\Form;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\FluidAdaptor\ViewHelpers\FormViewHelper;

/**
 * A view helper which generates an <input type="file"> HTML element.
 * Make sure to set enctype="multipart/form-data" on the form!
 *
 * If a file has been uploaded successfully and the form is re-displayed due to validation errors,
 * this ViewHelper will render hidden fields that contain the previously generated resource so you
 * won't have to upload the file again.
 *
 * You can use a separate ViewHelper to display previously uploaded resources in order to remove/replace them.
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:form.upload name="file" />
 * </code>
 * <output>
 * <input type="file" name="file" />
 * </output>
 *
 * <code title="Multiple Uploads">
 * <f:form.upload property="attachments.0.originalResource" />
 * <f:form.upload property="attachments.1.originalResource" />
 * </code>
 * <output>
 * <input type="file" name="formObject[attachments][0][originalResource]">
 * <input type="file" name="formObject[attachments][0][originalResource]">
 * </output>
 *
 * <code title="Default resource">
 * <f:form.upload name="file" value="{someDefaultResource}" />
 * </code>
 * <output>
 * <input type="hidden" name="file[originallySubmittedResource][__identity]" value="<someDefaultResource-UUID>" />
 * <input type="file" name="file" />
 * </output>
 *
 * <code title="Specifying the resource collection for the new resource">
 * <f:form.upload name="file" collection="invoices"/>
 * </code>
 * <output>
 * <input type="file" name="yourInvoice" />
 * <input type="hidden" name="yourInvoice[__collectionName]" value="invoices" />
 * </output>
 *
 * @api
 */
class UploadViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'input';

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerTagAttribute('disabled', 'string', 'Specifies that the input element should be disabled when the page loads');
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this view helper', false, 'f3-form-error');
        $this->registerArgument('collection', 'string', 'Name of the resource collection this file should be uploaded to', false, '');
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders the upload field.
     *
     * @return string
     * @api
     */
    public function render()
    {
        $nameAttribute = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($nameAttribute);

        $output = '';
        $resource = $this->getUploadedResource();
        if ($resource !== null) {
            $resourceIdentityAttribute = '';
            if ($this->hasArgument('id')) {
                $resourceIdentityAttribute = ' id="' . htmlspecialchars($this->arguments['id']) . '-resource-identity"';
            }
            $output .= '<input type="hidden" name="'. htmlspecialchars($nameAttribute) . '[originallySubmittedResource][__identity]" value="' . $this->persistenceManager->getIdentifierByObject($resource) . '"' . $resourceIdentityAttribute . ' />';
        }

        if ($this->hasArgument('collection') && $this->arguments['collection'] !== false && $this->arguments['collection'] !== '') {
            $output .= '<input type="hidden" name="'. htmlspecialchars($nameAttribute) . '[__collectionName]" value="' . htmlspecialchars($this->arguments['collection']) . '" />';
        }

        $this->tag->addAttribute('type', 'file');
        $this->tag->addAttribute('name', $nameAttribute);

        $this->addAdditionalIdentityPropertiesIfNeeded();
        $this->setErrorClassAttribute();

        $output .= $this->tag->render();
        return $output;
    }

    /**
     * Returns a previously uploaded resource, or the resource specified via "value" argument if no resource has been uploaded before
     * If errors occurred during property mapping for this property, NULL is returned
     *
     * @return PersistentResource or NULL if no resource was uploaded and the "value" argument is not set
     */
    protected function getUploadedResource()
    {
        $resource = null;
        if ($this->hasMappingErrorOccurred()) {
            $resource = $this->getLastSubmittedFormData();
        } elseif ($this->hasArgument('value')) {
            $resource = $this->arguments['value'];
        } elseif ($this->isObjectAccessorMode()) {
            $resource = $this->getPropertyValue();
        }
        if ($resource === null) {
            return null;
        }
        if ($resource instanceof PersistentResource) {
            return $resource;
        }
        return $this->propertyMapper->convert($resource, PersistentResource::class);
    }

    /**
     * Get the name of this form element, without prefix.
     *
     * Note: This is overridden here because the "value" argument should not have an effect on the name attribute of the <input type="file" /> tag
     * In the original implementation, setting a value will influence the name, @see AbstractFormFieldViewHelper::getNameWithoutPrefix()
     *
     * @return string name
     */
    protected function getNameWithoutPrefix()
    {
        if ($this->isObjectAccessorMode()) {
            $propertySegments = explode('.', $this->arguments['property']);
            $formObjectName = $this->viewHelperVariableContainer->get(FormViewHelper::class, 'formObjectName');
            if (!empty($formObjectName)) {
                array_unshift($propertySegments, $formObjectName);
            }
            $name = array_shift($propertySegments);
            foreach ($propertySegments as $segment) {
                $name .= '[' . $segment . ']';
            }
        } else {
            $name = $this->hasArgument('name') ? $this->arguments['name'] : '';
        }

        return $name;
    }
}
