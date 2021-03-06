<?php

namespace Icinga\Module\Ddolab;

abstract class StateObject extends DdoObject
{
    const FLAG_DOWNTIME   = 1;
    const FLAG_ACK        = 2;
    const FLAG_HOST_ISSUE = 4;
    const FLAG_NONE       = 8;
    const SHIFT_FLAGS     = 4;

    const ICINGA_OK          = 0;
    const ICINGA_WARNING     = 1;
    const ICINGA_CRITICAL    = 2;
    const ICINGA_UNKNOWN     = 3;
    const ICINGA_UP          = 0;
    const ICINGA_DOWN        = 2;
    const ICINGA_UNREACHABLE = 3; // TODO: re-think "reachable"
    const ICINGA_PENDING     = 99;

    protected static $hostStateSortMap = array(
        self::ICINGA_UP          => 0,
        self::ICINGA_PENDING     => 1,

        self::ICINGA_UNKNOWN     => 8,
        self::ICINGA_DOWN        => 8,

        // Hint: exit code 3 is mapped to down, "unreachable" needs to be calculated.
        // TODO: let "reachable" flow into severity and state calculation
        self::ICINGA_UNREACHABLE => 8,

        // Hint: exit code 1 is OK for Icinga 2.
        // Fits Icinga 1.x unless aggressive_host_checking is set
        self::ICINGA_WARNING     => 0,
    );

    // Reversing the above:
    protected static $hostSortStateMap = array(
        0 => self::ICINGA_UP,
        1 => self::ICINGA_PENDING,
        8 => self::ICINGA_DOWN,

        // TODO: these do currently not exist:
        2 => self::ICINGA_UNREACHABLE,
        4 => self::ICINGA_UNKNOWN,
    );

    protected static $serviceStateSortMap = array(
        self::ICINGA_OK       => 0,
        self::ICINGA_PENDING  => 1,
        self::ICINGA_WARNING  => 2,
        self::ICINGA_UNKNOWN  => 4,
        self::ICINGA_CRITICAL => 8,
    );

    protected static $serviceSortStateMap = array(
        0 => self::ICINGA_OK,
        1 => self::ICINGA_PENDING,
        2 => self::ICINGA_WARNING,
        4 => self::ICINGA_UNKNOWN,
        8 => self::ICINGA_CRITICAL,
    );

    protected static $hostStateNames = array(
        self::ICINGA_UP          => 'up',
        self::ICINGA_DOWN        => 'down',
        self::ICINGA_UNREACHABLE => 'unreachable',
        self::ICINGA_UNKNOWN     => 'unknown',
        self::ICINGA_PENDING     => 'pending',
    );

    protected static $namesToState = array(
        'up'          => self::ICINGA_UP,
        'ok'          => self::ICINGA_OK,
        'down'        => self::ICINGA_DOWN,
        'unreachable' => self::ICINGA_UNREACHABLE,
        'warning'     => self::ICINGA_WARNING,
        'critical'    => self::ICINGA_CRITICAL,
        'unknown'     => self::ICINGA_UNKNOWN,
        'pending'     => self::ICINGA_PENDING,
    );

    protected static $stateTypes = array(
        'soft',
        'hard',
    );

    protected $volatile;

    public function processCheckResult($result, $timestamp)
    {
        $vars = $result->vars_after;

        $currentState = (int) $result->state;

        if ($this->state === null || $currentState !== (int) $this->state) {
            $this->last_state_change = $timestamp;
        }

        $this->state        = $currentState;
        $this->state_type   = $vars->state_type;
        $this->problem      = $currentState > 0;
        $this->reachable    = $vars->reachable;
        $this->attempt      = $vars->attempt;
        $this->check_source_checksum = sha1($result->check_source, true);

        // TODO: Handle those
        $this->severity = $this->calculateSeverity();

        $volatileKeys = array(
            'command',
            'execution_start',
            'execution_end',
            'schedule_start',
            'schedule_end',
            'exit_status',
            'output',
            'performance_data'
        );
        $this->volatile = array();
        foreach ($volatileKeys as $key) {
            $this->volatile[$key] = $result->$key;
        }

        if ($this->hasBeenModified()) {
            $this->last_update = time();
        }
    }

    public static function getStateForName($name)
    {
        return self::$namesToState[$name];
    }

    public static function hostSeverityStateName($severity)
    {
        $state = self::$hostSortStateMap[$severity >> self::SHIFT_FLAGS];
        return self::$hostStateNames[$state];
    }

    public function processDowntimeAdded($result, $timestamp)
    {
        echo "Got downtime\n";
        print_r($result);
    }

    public function processDowntimeRemoved($result, $timestamp)
    {
        echo "Remove downtime\n";
        print_r($result);
    }

    public function processDowntimeTriggered($result, $timestamp)
    {
        echo "Triggered downtime\n";
        print_r($result);
    }

    public function processAcknowledgementSet($result, $timestamp)
    {
        $this->set('acknowledged', true);
        $this->set('severity', $this->calculateSeverity());
    }

    public function processAcknowledgementCleared($result, $timestamp)
    {
        $this->set('acknowledged', false);
        $this->set('severity', $this->calculateSeverity());
    }

    public function setState_type($type)
    {
        if (ctype_digit((string) $type)) {
            $type = self::$stateTypes[(int) $type];
        }

        return $this->reallySet('state_type', $type);
    }

    /*
    // Draft for history updates
    public function storeStateChange()
    {
        $this->db->insert('state_history', array(
            'timestamp' => $this->timestamp,
            'state'     => '',
            '' => '',
            '' => '',
        ));
    }
    */

    /*
    // Draft, showing how we could deal with sla history
    public function refreshSlaTable()
    {
        $db = $this->db;

        'UPDATE sla_table SET duration = ? - start_time, end_time = ?'
        . ' WHERE object_checksum = ? AND end_time = ?',
        $this->timestamp,
        $this->checksum,
        self::TIME_INFINITY

        $db->insert(
            'sla_table',
            array(
                'object_checksum' => $this->checksum,
                'acknowledged'    => $this->acknowledged,
                'in_downtime'     => $this->in_downtime,
            )
        );
    }
    */

    public function recalculateSeverity()
    {
        $this->set('severity', $this->calculateSeverity());
        return $this;
    }

    protected function calculateSeverity()
    {
        $sev = $this->getSortingState() << self::SHIFT_FLAGS;
        if ($this->isInDowntime()) {
            $sev |= self::FLAG_DOWNTIME;
        } elseif ($this->isAcknowledged()) {
            $sev |= self::FLAG_ACK;
        } else {
            $sev |= self::FLAG_NONE;
        }

        return $sev;
    }

    public function isProblem()
    {
        return $this->get('problem') === 'y';
    }

    public function isInDowntime()
    {
        return $this->get('in_downtime') === 'y';
    }

    public function isAcknowledged()
    {
        return $this->get('acknowledged') === 'y';
    }

    public function getUniqueName()
    {
        $key = $this->get('host');
        if ($this->hasProperty('service')) {
            $key .= '!' . $this->get('service');
        }

        return $key;
    }

    abstract protected function getSortingState();
}
