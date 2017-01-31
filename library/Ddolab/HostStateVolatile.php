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

    public static function enrichRow($row)
    {
        if ((int) $row->state === 99) {
            return;
        }

        if ($redis = static::$predis) {
            foreach (static::fromRedis($redis, $row->checksum)->getProperties() as $key => $value) {
                $row->$key = $value;
            }
        }
    }

    public static function fromRedis(Client $redis, $checksum)
    {
        if (is_array($checksum)) {
            $keys = array_map($checksum, 'HostStateVolatile::prefix');
            $encoded = $redis->mget($keys);
            return array_map($encoded, 'json_decode');
        } else {
            $res = json_decode(
                $redis->get(HostStateVolatile::prefix($checksum))
            );
            if ($res) {
                return static::create((array) $res);
            } else {
                return static::create(array());
            }
        }
    }

    public static function removeFromRedis(Client $redis, $checksum)
    {
        $redis->del(HostStateVolatile::prefix($checksum));
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
