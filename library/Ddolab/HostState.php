<?php

namespace Icinga\Module\Ddolab;

class HostState extends StateObject
{
    protected $table = 'host_state';

    protected $keyName = 'checksum';

    protected $defaultProperties = array(
        // active ?
        'checksum'              => null,
        'host'                  => null,
        'state'                 => null,
        'hard_state'            => null,
        'state_type'            => null,
        'attempt'               => null,
        'problem'               => 'n',
        'reachable'             => 'y',
        'severity'              => null,
        'acknowledged'          => 'n',
        'in_downtime'           => 'n',
        'last_update'           => null, // only on store if modified
        'last_state_change'     => null,
        'last_comment_checksum' => null,
        'check_source_checksum' => null,
    );

    protected $booleans = array(
        'problem',
        'reachable',
        'acknowledged',
        'in_downtime'
    );

    protected $timestamps = array(
        'last_update',
        'last_state_change',
    );

    /**
     * @return HostStateVolatile
     */
    public function getVolatile()
    {
        $props = $this->volatile;
        $props['host_checksum'] = $this->get('checksum');
        return HostStateVolatile::create($props);
    }

    protected function getSortingState()
    {
        return self::$hostStateSortMap[$this->state];
    }
}
