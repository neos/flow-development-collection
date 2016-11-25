<?php
namespace Neos\FluidAdaptor\Core\Parser;

/**
 * This is needed to support the EscapingFlagProcessor and globally (en|dis)able escaping in the template.
 */
class TemplateParser extends \TYPO3Fluid\Fluid\Core\Parser\TemplateParser
{
    /**
     * @return boolean
     */
    public function isEscapingEnabled()
    {
        return $this->escapingEnabled;
    }

    /**
     * @param boolean $escapingEnabled
     */
    public function setEscapingEnabled($escapingEnabled)
    {
        $this->escapingEnabled = $escapingEnabled;
    }
}
