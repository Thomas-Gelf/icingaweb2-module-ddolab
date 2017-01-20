<?php

namespace Icinga\Module\Ddolab;

use Icinga\Module\Director\Data\Db\DbConnection;

class DdoDb extends DbConnection
{
    public function isPgsql()
    {
        // TODO(tg/el): Not PostgreSQL support yet
        return false;
//        return $this->getDbType() === 'pgsql';
    }
}
