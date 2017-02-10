<?php

namespace Icinga\Module\Ddolab\Web\Component;

use Icinga\Exception\ProgrammingError;
use Icinga\Module\Businessprocess\Html\BaseElement;
use Icinga\Module\Businessprocess\Html\Element;
use Icinga\Module\Businessprocess\Html\Link;
use Icinga\Module\Ddolab\Db\StateSummary\StateSummary;
use Icinga\Module\Ddolab\StateObject;

abstract class StateSummaryBadges extends BaseElement
{
    protected $contentSeparator = ' ';

    /** @var string */
    protected $tag = 'div';

    /** @inheritdoc */
    protected $defaultAttributes = array(
        'class' => 'statesummary',
    );

    /** @var string */
    protected $baseUrl;

    public function __construct(StateSummary $states)
    {
        $inUse = array();
        foreach ($states->fetch() as $state => $count) {
            $stateName = $this->getStateName($state);
            if (! array_key_exists($stateName, $inUse)) {
                $inUse[$stateName] = array();
            }

            if (($state & StateObject::FLAG_NONE) === StateObject::FLAG_NONE) {
                $inUse[$stateName]['unhandled'] = $count;
            } else {
                $inUse[$stateName]['handled'] = $count;
            }
        }

        foreach ($inUse as $stateName => $counts) {
            $ul = Element::create('ul', array('class' => $stateName));
            $this->addItem($ul, 'unhandled', $stateName, $counts, 'n');
            $this->addItem($ul, 'handled', $stateName, $counts, 'y', array('class' => 'handled'));
            $this->add($ul);
        }
    }

    protected function addItem(Element $ul, $key, $stateName, $counts, $handled, $attrs = null)
    {
        if (array_key_exists($key, $counts)) {
            $ul->add(
                Element::create('li', $attrs)->setContent(
                    $this->makeLink($counts[$key], $stateName, $handled)
                )
            );
        }

        return $this;
    }

    public function getBaseUrl()
    {
        if ($this->baseUrl === null) {
            throw new ProgrammingError(
                'StateSummaryBadges implementations need a baseUrl'
            );
        }

        return $this->baseUrl;
    }

    protected function makeLink($count, $stateName, $handled)
    {
        return Link::create(
            $count,
            $this->baseUrl,
            array(
                'state'   => $stateName,
                'handled' => $handled
            )
        );
    }

    abstract public function getStateName($state);
}
