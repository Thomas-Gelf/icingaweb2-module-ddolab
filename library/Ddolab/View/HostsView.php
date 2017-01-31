<?php

namespace Icinga\Module\Ddolab\View;

use Predis\Client;

class HostsView extends ListView
{
    protected $predis;

    public function getAvailableColumns()
    {
        return array(

        );
    }

    public function setRedis(Client $predis)
    {
        $this->predis = $predis;
        return $this;
    }

    public function getColumns()
    {
        return array(
            'host'              => 'h.name',
            'checksum'          => 'h.checksum',
            'state'             => 'hs.state',
            'problem'           => 'hs.problem',
            'acknowledged'      => 'hs.acknowledged',
            'in_downtime'       => 'hs.in_downtime',
            'last_state_change' => 'hs.last_state_change',
        );
    }

    protected function prepareBaseQuery()
    {
        return $this->db()
            ->select()
            ->from(
                array('h' => 'ddo_host'),
                array()
            )->join(
                array('hs' => 'host_state'),
                'h.checksum = hs.checksum',
                array()
            )
            ->order('severity DESC')
            ->order('last_state_change DESC')
            ->limit(25);
    }
}
