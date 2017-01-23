<?php

namespace Icinga\Module\Ddolab;

use Icinga\Module\Director\Core\CoreApi;
use Icinga\Application\Logger;
use Exception;

class IcingaEventToRedisStreamer
{
    protected $redis;

    protected $api;

    public function __construct(CoreApi $api)
    {
        $this->api = $api;
    }

    public function stream()
    {
        $attempts = 0;
        while (true) {
            try {
                $lastAttempt = time();
                $attempts++;
                $this->api->onEvent(array($this, 'enqueueEvent'), true)->stream();
            } catch (Exception $e) {
                Logger::error($e->getMessage());
            }

            $this->clearConnections();
            if ($attempts > 5) {
                Logger::info('(ddolab) Waiting 5 seconds for reconnect');
                $attempts = 0;
                sleep(5);
            } else {
                usleep(100000);
                Logger::info('(ddolab) Trying to reconnect');
            }
        }
    }

    // Must be accessible from outside, as this is a callback
    public function enqueueEvent(& $event)
    {
        while (true) {
            try {
                $id = $this->redis()->lpush('icinga2::events', $event);
                Logger::debug('(ddolab) Stored id %d', $id);
                return;
            } catch (Exception $e) {
                Logger::error(
                    '(ddolab) Could not enqueue event to redis, will retry: %s',
                    $e->getMessage()
                );
                $this->redis = null;
                sleep(5);
            }
        }
    }

    protected function clearConnections()
    {
        $this->redis = null;
        // Really?
        // unset($this->api);
    }

    protected function redis()
    {
        if ($this->redis === null) {
            $this->redis = Redis::instance(true);
        }

        return $this->redis;
    }

    public function __destruct()
    {
        $this->clearConnections();
    }
}
