<?php

namespace Icinga\Module\Ddolab\Db\StateSummary;

use Icinga\Data\Filter\Filter;
use Icinga\Exception\ProgrammingError;
use Icinga\Module\Ddolab\DdoDb;

class StateSummary
{
    /** @var DdoDb */
    protected $db;

    protected $data;

    protected $filter;

    protected $table;

    protected function __construct()
    {
    }

    public static function fromDb(DdoDb $db)
    {
        $summary = new static();
        $summary->db = $db;
        return $summary;
    }

    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;
        throw new ProgrammingError('Not yet');
    }

    public function fetch()
    {
        if ($this->data === null) {
            $this->data = $this->fetchDataFromDb();
        }

        return $this->data;
    }

    public function getTableName()
    {
        if ($this->table === null) {
            throw new ProgrammingError('StateSummary implementation needs a table');
        }

        return $this->table;
    }

    protected function fetchDataFromDb()
    {
        $db = $this->db->getDbAdapter();
        return $db->fetchPairs(
            $db->select()->from(
                array('s' => $this->getTableName()),
                array(
                    'severity' => 's.severity',
                    'cnt' => 'COUNT(*)',
                )
            )->group('s.severity')
        );
    }
}
