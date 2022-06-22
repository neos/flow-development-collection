<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Log\PsrLoggerFactory;

/**
 * Adjust "Settings.yaml" to new naming of settings (see https://github.com/neos/flow-development-collection/pull/2051)
 */
class Version20200813181400 extends AbstractMigration
{

    public function getIdentifier()
    {
        return 'Neos.Flow-20200813181400';
    }

    /**
     * @return void
     */
    public function up()
    {
        $this->moveSettingsPaths('Neos.Flow.resource.uploadExtensionBlacklist','Neos.Flow.resource.extensionsBlockedFromUpload');
    }
}
