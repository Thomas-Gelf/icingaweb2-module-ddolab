<?php

namespace Icinga\Module\Ddolab;

use Icinga\Module\Ddolab\DdoDb;
use Icinga\Module\Ddolab\Redis;
use Icinga\Module\Ddolab\StateList;

class IcingaEventHandler
{
    protected $redis;

    protected $api;

    public function __construct(DdoDb $db)
    {
        $this->db = $db;
    }

    public function processEvents()
    {
        $time = time();
        $cnt = 0;
        $cntEvents = 0;
        $hasTransaction = false;
        $ddo = $this->ddo();
        $db = $ddo->getDbAdapter();
        $list = new StateList($ddo);

        // TODO: 0 is forever, leave loop after a few sec and enter again
        while (true) {
            $redis = $this->redis();

            while ($res = $redis->brpop('icinga2::events', 1)) {
                $cntEvents++;
                // res = array(queuename, value)
                $object = $list->processCheckResult(json_decode($res[1]));
                if ($object === false) {
                    continue;
                }

                if ($object->hasBeenModified() && $object->state !== null) {
                    Logger::info('(ddolab) %s has been modified', $object->getUniqueName());

                    if (! $hasTransaction) {
                        $db->beginTransaction();
                        $hasTransaction = true;
                    }
                    $cnt++;
                    $object->store();
                } else {
                    Logger::debug('(ddolab) %s has not been modified', $object->getUniqueName());
                }

                if (($cnt >= 1000)
                    || ($cnt > 0 && (($newtime = time()) - $time > 1))
                ) {
                    $time = $newtime;
                    Logger::info('(ddolab) Committing %d events (%d total)', $cnt, $cntEvents);
                    $cnt = 0;
                    $cntEvents = 0;
                    $db->commit();
                    $hasTransaction = false;
                }
            }

            // printf("%s: %d events\n", date("H:i:s"), $cntEvents);
            // echo "Got nothing for 1secs\n";

            if ($cnt > 0) {
                $time = time();
                Logger::info('(ddolab) Committing %d events (%d total)', $cnt, $cntEvents);
                $cnt = 0;
                $cntEvents = 0;
                $db->commit();
                $hasTransaction = false;
            }
        }
    }


    public function streamAction()
    {
        $attempts = 0;
        while (true) {
            try {
                $lastAttempt = time();
                $attempts++;
                $this->api->onEvent(array($this, 'enqueueEvent'))->stream();
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
    public function enqueueEvent($event)
    {
        while (true) {
            try {
                $id = $this->redis()->lpush('icinga2::events', json_encode($event));
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

    protected function redis()
    {
        if ($this->redis === null) {
            $this->redis = Redis::instance(true);
        }

        return $this->redis;
    }

    public function __destruct()
    {
        unset($this->redis);
        unset($this->api);
    }
}
