<?php

namespace Icinga\Module\Ddolab\Cli;

use Icinga\Module\Ddolab\DdoDb;
use Icinga\Module\Ddolab\Redis;
use Icinga\Module\Director\Cli\Command as DirectorCommand;

class Command extends DirectorCommand
{
    private $ddo;

    private $redis;

    /**
     * @return Redis;
     */
    protected function redis()
    {
        if ($this->redis === null) {
            $this->redis = Redis::instance(true);
        }

        return $this->redis;
    }

    protected function ddo()
    {
        if ($this->ddo === null) {
            $resourceName = $this->Config()->get('db', 'resource');
            if ($resourceName) {
                $this->ddo = DdoDb::fromResourceName($resourceName);
            } else {
                $this->fail('(ddolab) DDO is not configured correctly');
            }
        }

        return $this->ddo;
    }

    protected function clearConnections()
    {
        $this->ddo   = null;
        $this->redis = null;
        return $this;
    }
}
