<?php

namespace Icinga\Module\Ddolab\Cli;

use Icinga\Module\Ddolab\DdoDb;
use Icinga\Module\Ddolab\Redis;
use Icinga\Module\Director\Cli\Command as DirectorCommand;

class Command extends DirectorCommand
{
    /** @var DdoDb */
    private $ddo;

    /** @var Redis */
    private $redis;

    /**
     * @return \Predis\Client
     */
    protected function redis()
    {
        if ($this->redis === null) {
            $this->redis = Redis::instance(true);
        }

        return $this->redis;
    }

    /**
     * @return DdoDb
     */
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
