<?php

namespace Icinga\Module\Ddolab\Clicommands;

use Exception;
use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\Module\Ddolab\Cli\Command;
use Icinga\Module\Ddolab\ObjectSync;

/**
 * Icinga Object Config related tasks
 */
class ConfigCommand extends Command
{
    /**
     * Sync current config from Icinga API to DDO
     *
     * This command runs forver and regularly syncs object configuration to DDO.
     * Default interval is 60 seconds, please use --sleep <seconds> to adjust
     * this to fit your needs.
     */
    public function syncAction()
    {
        $sleepSeconds = (int) $this->params->get('sleep', 60);
        $sync = new ObjectSync($this->api(), $this->ddo());
        $sync->syncForever($sleepSeconds);
    }
}
