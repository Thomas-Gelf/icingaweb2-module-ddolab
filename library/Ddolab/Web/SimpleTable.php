<?php

namespace Icinga\Module\Ddolab\Web;

use Icinga\Module\Ddolab\Web\Component\Table;

class SimpleTable extends Table
{
    /** @inheritdoc */
    protected $defaultAttributes = array(
        'class'   => array('simple', 'common-table'),
    );

    public function getColumnsToBeRendered()
    {
        return array('host', 'state');
    }
}
