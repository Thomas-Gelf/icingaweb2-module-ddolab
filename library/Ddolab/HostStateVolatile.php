<?php

namespace Icinga\Module\Ddolab;

use Predis\Client;

class HostStateVolatile extends DdoObject
{
    const PREFIX = 'IcingaHostStateVolatile::';

    protected $table = 'host_state_volatile';

    protected $keyName = 'host_checksum';

    protected $defaultProperties = array(
        'host_checksum'    => null,
        'command'          => null, // JSON, array
        'execution_start'  => null,
        'execution_end'    => null,
        'schedule_start'   => null,
        'schedule_end'     => null,
        'exit_status'      => null,
        'output'           => null,
        'performance_data' => null, // JSON, array
    );

    /** @var Client */
    private static $predis;

    public static function setRedis(Client $redis)
    {
        static::$predis = $redis;
    }

    public function storeToRedis(Client $redis)
    {
        $redis->set(static::prefix($this->get('host_checksum')), $this->toJson());
        return $this;
    }

    protected static function prefix($key)
    {
        return HostStateVolatile::PREFIX . $key;
    }

    public function toJson()
    {
        $props = (object) $this->getProperties();
        unset($props->host_checksum);
        return json_encode($props);
    }
}
