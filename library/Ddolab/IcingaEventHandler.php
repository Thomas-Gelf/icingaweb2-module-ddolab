<?php

namespace Icinga\Module\Ddolab;

use Icinga\Application\Logger;
use Icinga\Module\Director\Core\CoreApi;

class IcingaEventHandler
{
    /** @var \Predis\Client */
    protected $redis;

    /** @var CoreApi */
    protected $api;

    /** @var DdoDb */
    protected $ddo;

    /** @var \Zend_Db_Adapter_Abstract */
    protected $db;

    protected $hasTransaction = false;

    protected $useTransactions = true;

    /**
     * IcingaEventHandler constructor.
     * @param DdoDb $ddo
     */
    public function __construct(DdoDb $ddo)
    {
        $this->ddo = $ddo;
        $this->db = $ddo->getDbAdapter();
    }

    public function processEvents()
    {
        $time = time();
        $cnt = 0;
        $cntEvents = 0;
        $ddo = $this->ddo;
        $db = $this->db;
        $list = new StateList($ddo);

        // TODO: 0 is forever, leave loop after a few sec and enter again
        while (true) {
            $redis = $this->redis();

            while ($res = $redis->brpop('icinga2::events', 1)) {
                $cntEvents++;
                // Hint: $res = array(queuename, value)
                $object = $list->processCheckResult(json_decode($res[1]));
                if ($object === false) {
                    continue;
                }

                if ($object->hasBeenModified() && $object->state !== null) {
                    Logger::info('(ddolab) %s has been modified', $object->getUniqueName());
                    $this->wantsTransaction();
                    $cnt++;
                    $object->store();
                } else {
                    Logger::debug('(ddolab) %s has not been modified', $object->getUniqueName());
                }

                if (($cnt >= 1000 && $newtime = time())
                    || ($cnt > 0 && (($newtime = time()) - $time > 1))
                ) {
                    $time = $newtime;
                    Logger::info('(ddolab) Committing %d events (%d total)', $cnt, $cntEvents);
                    $cnt = 0;
                    $cntEvents = 0;
                    $this->closeTransaction();
                }
            }

            if ($cnt > 0) {
                $time = time();
                Logger::info('(ddolab) Committing %d events (%d total)', $cnt, $cntEvents);
                $cnt = 0;
                $cntEvents = 0;
                $this->closeTransaction();
            }
        }
    }

    protected function wantsTransaction()
    {
        if ($this->useTransactions && ! $this->hasTransaction) {
            $this->db->beginTransaction();
            $this->hasTransaction = true;
        }
    }

    protected function closeTransaction()
    {
        if ($this->hasTransaction) {
            // TODO: try, rollback
            $this->db->commit();
            $this->hasTransaction = false;
        }
    }

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

    public function __destruct()
    {
        unset($this->redis);
        unset($this->api);
        unset($this->db);
        unset($this->ddo);
    }
}
