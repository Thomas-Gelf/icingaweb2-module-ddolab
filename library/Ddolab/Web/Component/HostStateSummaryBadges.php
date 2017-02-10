<?php

namespace Icinga\Module\Ddolab\Web\Component;

use Icinga\Module\Ddolab\HostState;

class HostStateSummaryBadges extends StateSummaryBadges
{
    protected $baseUrl = 'ddolab/hosts';

    public function getStateName($state)
    {
        return HostState::hostSeverityStateName($state);
    }
}
