<?php

namespace Icinga\Module\Director\Ddo;

class HostStateVolatile extends DdoObject
{
    protected $defaultProperties = array(
        'command'          => null, // JSON, array
        'execution_start'  => null,
        'execution_end'    => null,
        'schedule_start'   => null,
        'schedule_end'     => null,
        'exit_status'      => null,
        'output'           => null,
        'performance_data' => null, // JSON, array
    );
}
