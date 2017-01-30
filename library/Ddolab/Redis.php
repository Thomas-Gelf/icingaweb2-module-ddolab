<?php

namespace Icinga\Module\Ddolab;

use Icinga\Application\Config;
use Predis\Client as PredisClient;

class Redis
{
    protected static $redis;

    /**
     * @param bool $new
     * @return PredisClient
     */
    public static function instance($new = false)
    {
        if ($new || self::$redis === null) {
            $config = Config::module('ddolab', 'config', true);

            $options = array(
                'host' => $config->get('redis', 'host', 'localhost'),
                'port' => $config->get('redis', 'port', 6379),
            );

            if ($password = $config->get('redis', 'password', null)) {
                $options['password'] = $password;
            }

            require_once dirname(__DIR__) . '/vendor/predis/autoload.php';
            self::$redis = new PredisClient($options);
        }
        return self::$redis;
    }
}
