<?php

use Behat\Behat\Context\Context;
use Neos\Behat\FlowBootstrapTrait;
use Neos\Behat\FlowEntitiesTrait;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\ResourceOperationsTrait;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;
use Neos\Flow\Tests\Functional\Command\BehatTestHelper;
use PHPUnit\TextUI\CliArguments\Builder as CliConfigurationBuilder;
use PHPUnit\TextUI\Configuration\Registry as PhpUnitConfigurationRegistry;
use PHPUnit\TextUI\XmlConfiguration\DefaultConfiguration;

require_once(__DIR__ . '/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/SecurityOperationsTrait.php');
require_once(__DIR__ . '/ResourceOperationsTrait.php');

/**
 * See {@see IsolatedBehatStepsTrait} documentation for a detailed explanation of Flow's isolated behat tests.
 *
 * @deprecated todo the policy features depending on this handcrafted isolated behat test infrastructure will be refactored and this infrastructure removed.
 * @internal only allowed to be used internally for Neos.Flow behavioral tests!
 */
class FeatureContext implements Context
{
    use FlowBootstrapTrait;
    use FlowEntitiesTrait;
    use IsolatedBehatStepsTrait;
    use SecurityOperationsTrait;
    use ResourceOperationsTrait;

    /**
     * @var string
     */
    protected $behatTestHelperObjectName = BehatTestHelper::class;

    public function __construct()
    {
        self::bootstrapFlow();
        $this->setupSecurity();
        PhpUnitConfigurationRegistry::init(
            (new CliConfigurationBuilder())->fromParameters([]),
            DefaultConfiguration::create()
        );
    }
}
