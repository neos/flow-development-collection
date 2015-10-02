<?php
namespace TYPO3\Fluid\ViewHelpers\Form;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Resource\Resource;

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
        $name = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($name);

        $output = '';
        $resourceObject = $this->getUploadedResource();
        if ($resourceObject !== null) {
            $filenameIdAttribute = $resourcePointerIdAttribute = '';
            if ($this->hasArgument('id')) {
                $filenameIdAttribute = ' id="' . htmlspecialchars($this->arguments['id']) . '-filename"';
                $resourcePointerIdAttribute = ' id="' . htmlspecialchars($this->arguments['id']) . '-resourcePointer"';
            }
            $filenameValue = $resourceObject->getFilename();
            $resourcePointerValue = $resourceObject->getResourcePointer();
            $output .= '<input type="hidden" name="' . $this->getName() . '[submittedFile][filename]" value="' . htmlspecialchars($filenameValue) . '"' . $filenameIdAttribute . ' />';
            $output .= '<input type="hidden" name="' . $this->getName() . '[submittedFile][resourcePointer]" value="' . htmlspecialchars($resourcePointerValue) . '"' . $resourcePointerIdAttribute . ' />';
        }

        $this->tag->addAttribute('type', 'file');
        $this->tag->addAttribute('name', $name);

        $this->setErrorClassAttribute();

        $output .= $this->tag->render();
        return $output;
    }

    /**
     * Returns a previously uploaded resource.
     * If errors occurred during property mapping for this property, NULL is returned
     *
     * @return \TYPO3\Flow\Resource\Resource
     */
    protected function getUploadedResource()
    {
        if ($this->getMappingResultsForProperty()->hasErrors()) {
            return null;
        }
        $resourceObject = $this->getValue(false);
        if ($resourceObject instanceof Resource) {
            return $resourceObject;
        }
        return $this->propertyMapper->convert($resourceObject, 'TYPO3\Flow\Resource\Resource');
    }
}
