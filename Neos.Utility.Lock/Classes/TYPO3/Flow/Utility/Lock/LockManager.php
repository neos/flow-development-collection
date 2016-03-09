<?php
namespace TYPO3\Flow\Utility\Lock;

/**
 * This Lock manager should be used as a singleton and keeps information which LockStrategyInteface to use and the options.
 */
class LockManager
{
    /**
     * @var string
     */
    protected $lockStrategyClassName;

    /**
     * @var array
     */
    protected $lockStrategyOptions;

    /**
     * LockManager constructor.
     *
     * @param $lockStrategyClassName
     * @param array $lockStrategyOptions
     */
    public function __construct($lockStrategyClassName, array $lockStrategyOptions = [])
    {
        if (!class_exists($lockStrategyClassName)) {
            throw new \InvalidArgumentException('The given class name given as implementation of the LockStrategyInterface does not exist!', 1454694738);
        }

        $this->lockStrategyClassName = $lockStrategyClassName;
        $this->lockStrategyOptions = $lockStrategyOptions;
    }

    /**
     * @return LockStrategyInterface
     */
    public function getLockStrategyInstance()
    {
        $lockStrategy = $this->lockStrategyClassName;
        return new $lockStrategy($this->lockStrategyOptions);
    }
}