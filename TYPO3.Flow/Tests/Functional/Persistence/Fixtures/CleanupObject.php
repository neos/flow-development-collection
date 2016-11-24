<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

/***************************************************************
 *  (c) 2016 networkteam GmbH - all rights reserved
 ***************************************************************/

/**
 * Class CleanupObject
 */
class CleanupObject
{
    /**
     * @var boolean
     */
    protected $state = false;

    public function toggleState()
    {
        $this->state = !$this->state;
    }

    /**
     * @return boolean
     */
    public function getState()
    {
        return $this->state;
    }
}
