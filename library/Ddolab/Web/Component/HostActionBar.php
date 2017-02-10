<?php

namespace Icinga\Module\Ddolab\Web\Component;

use Icinga\Module\Businessprocess\Html\Link;
use Icinga\Module\Businessprocess\Web\Component\ActionBar;
use Icinga\Module\Ddolab\HostObject;
use Icinga\Module\Ddolab\HostState;

class HostActionBar extends ActionBar
{
    public function __construct(HostObject $host, HostState $state)
    {
        $this->add(
            Link::create('Acknowledge', 'ack', null, array('class' => 'icon-edit'))
        );
        $this->add(
            Link::create('Check Now', 'ack', null, array('class' => 'icon-reschedule'))
        );
        $this->add(
            Link::create('Comment', 'ack', null, array('class' => 'icon-comment-empty'))
        );
        $this->add(
            Link::create('Notification', 'ack', null, array('class' => 'icon-bell'))
        );
        $this->add(
            Link::create('Downtime', 'ack', null, array('class' => 'icon-plug'))
        );
    }
}
