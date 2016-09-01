<?php

namespace Icinga\Module\Ddolab;

/**
 * A DDO host group member
 */
class HostGroupMember extends DdoObject
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'ddo_host_group_member';

    /**
     * {@inheritdoc}
     */
    protected $keyName = array('host_group_checksum', 'host_checksum');

    /**
     * {@inheritdoc}
     */
    protected $defaultProperties = array(
        'host_group_checksum'   => null,
        'host_checksum'         => null
    );
}

