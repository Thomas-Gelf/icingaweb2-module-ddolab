<?php

namespace Icinga\Module\Ddolab;

use Exception;
use Icinga\Application\Logger;
use Icinga\Module\Director\Core\CoreApi;
use Icinga\Module\Ddolab\DdoDb;
use Icinga\Module\Ddolab\HostGroup;
use Icinga\Module\Ddolab\HostGroupMember;
use Icinga\Module\Ddolab\HostObject;

class ObjectSync
{
    protected $api;

    protected $connection;

    protected $storedHosts;

    protected $storedHostGroups;

    protected $activeHosts;

    protected $activeHostGroups;

    public function __construct(CoreApi $api, DdoDb $connection)
    {
        $this->api = $api;
        $this->connection = $connection;
        $this->db = $connection->getDbAdapter();
    }

    public function syncForever($sleepSeconds)
    {
        Logger::info(
            '(ddolab) Config sync started, will refresh every %s seconds',
            $sleepSeconds
        );

        while (true) {
            try {
                $this->syncAll();
            } catch (Exception $e) {
                Logger::error($e->getMessage());
                $this->clearConnections();
            }

            sleep($sleepSeconds);
        }
    }

    public function syncAll()
    {
        Logger::debug('(ddolab) Syncing all objects');

        // Order matters!
        $start = microtime(true);
        $this->loadStoredObjects();
        $loaded = microtime(true);
        $this->fetchActiveObjects();
        $fetched = microtime(true);
        $this->syncHostGroups();
        $this->syncHosts();
        $synched = microtime(true);
        $this->removeObsoleteHosts();
        $this->removeObsoleteHostGroups();
        $removed = microtime(true);

        Logger::debug(
            '(ddolab) Sync done, spend %.2Fms fetching objects from db, %.2Fms'
            . ' fetching from Icinga 2 API, %.2Fms on sync and %.2Fms to remove'
            . ' obsolete objects from DB',
            ($loaded - $start) * 1000,
            ($fetched - $loaded) * 1000,
            ($synched - $fetched) * 1000,
            ($removed - $synched) * 1000
        );
/*
        foreach ($apiHost->attrs->groups as $hostGroup) {
            $member = HostGroupMember::create(
                array(
                    'host_group_checksum'   => hex2bin(sha1($hostGroup)),
                    'host_checksum'         => $ddoHost->checksum
                ),
                $db
            );
            try {
                // Brute force atm
                $member->store();
                Logger::debug('Updating host group memberships for host %s', $name);
            } catch (Exception $e) {
                // TODO(el): Member cleanup
                continue;
            }
        }
*/
    }

    protected function syncHosts()
    {
        $this->syncObjects('Host');
    }

    protected function syncHostGroups()
    {
        $this->syncObjects('HostGroup');
    }

    protected function syncObjects($key)
    {
        $modified = array();
        $activeKey = 'active' . $key . 's';
        $storedKey = 'stored' . $key . 's';

        foreach ($this->$activeKey as $checksum => $object) {
            if (array_key_exists($checksum, $this->$storedKey)) {
                $stored = $this->$storedKey[$checksum]->replaceWith($object);
            } else {
                $stored = $this->$storedKey[$checksum] = $object;
            }

            if ($stored->hasBeenModified()) {
                if ($stored->hasBeenLoadedFromDb()) {
                    Logger::info(
                        '(ddolab) %s "%s" has been modified',
                        $storedKey,
                        $stored->name
                    );
                } else {
                    Logger::info(
                        '(ddolab) %s "%s" has been created',
                        $storedKey,
                        $stored->name
                    );
                }

                $modified[$checksum] = $stored;
            }
        }
        ksort($this->$storedKey);

        $this->storeModifiedObjects('host group', $modified);
    }

    protected function fetchActiveObjects()
    {
        $this->fetchActiveHosts();
        $this->fetchActiveHostGroups();
    }

    protected function fetchActiveHosts()
    {
        $objects = $this->api->getObjects(null, 'hosts');
        $db = $this->connection;

        $this->activeHosts = array();
        foreach ($objects as $name => $object) {
            $ddoObject = HostObject::fromApiObject($name, $object, $db);
            $this->activeHosts[$ddoObject->checksum] = $ddoObject;
        }

        ksort($this->activeHosts);
    }

    protected function fetchActiveHostGroups()
    {
        $objects = $this->api->getObjects(null, 'hostgroups');
        $db = $this->connection;

        $this->activeHostGroups = array();
        foreach ($objects as $name => $object) {
            $ddoObject = HostGroup::fromApiObject($name, $object, $db);
            $this->activeHostGroups[$ddoObject->checksum] = $ddoObject;
        }

        ksort($this->activeHostGroups);
    }

    protected function loadStoredObjects()
    {
        $this->loadStoredHosts();
        $this->loadStoredHostGroups();
    }

    protected function loadStoredHosts()
    {
        $this->storedHosts = HostObject::loadAll($this->connection, null, 'checksum');
        ksort($this->storedHosts);
    }

    protected function loadStoredHostGroups()
    {
        $this->storedHostGroups = HostGroup::loadAll($this->connection, null, 'checksum');
        ksort($this->storedHostGroups);
    }

    protected function storeModifiedObjects($label, $objects)
    {
        if (empty($objects)) {
            return;
        }

        $db = $this->db;
        $db->beginTransaction();

        try {
            foreach ($objects as $object) {
                Logger::info('(ddolab) Storing %s %s', $label, $object->name);
                $object->store();
            }

            $db->commit();

        } catch (Exception $e) {
            try {
                $db->rollBack();
            } catch (Exception $e) {
                Logger::error('(ddolab) DB rollback failed: ' . $e->getMessage());
            }

            throw $e;
        }
    }

    protected function removeObsoleteHosts()
    {
        $this->removeObsoleteObjects('Host', 'ddo_host');
    }

    protected function removeObsoleteHostGroups()
    {
        $this->removeObsoleteObjects('HostGroup', 'ddo_host_group');
    }

    protected function removeObsoleteObjects($key, $table)
    {
        $storedKey = 'stored' . $key . 's';
        $activeKey = 'active' . $key . 's';

        $remove = array_diff(
            array_keys($this->$storedKey),
            array_keys($this->$activeKey)
        );

        if (empty($remove)) {
            return;
        }

        $names = array();
        foreach ($remove as $checksum) {
            $names[] = $this->$storedKey[$checksum]->name;
        }

        Logger::info('(ddolab) Deleting %s: %s', $key, implode(', ', $names));

        $this->db->delete(
            $table,
            $this->db->quoteInto('checksum in (?)', $remove)
        );
    }

    public function __destruct()
    {
        unset($this->api);
        unset($this->db);
        unset($this->connection);
        unset($this->storedHosts);
        unset($this->storedHostGroups);
        unset($this->activeHosts);
        unset($this->activeHostGroups);
    }
}
