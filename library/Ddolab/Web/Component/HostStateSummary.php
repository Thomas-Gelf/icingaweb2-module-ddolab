<?php

namespace Icinga\Module\Ddolab\Web\Component;

use Icinga\Module\Businessprocess\Html\BaseElement;
use Icinga\Module\Businessprocess\Html\Element;
use Icinga\Module\Businessprocess\Html\Link;
use Icinga\Module\Ddolab\HostState;

class HostStateSummary extends BaseElement
{
    protected $contentSeparator = ' ';

    /** @var string */
    protected $tag = 'div';

    /** @inheritdoc */
    protected $defaultAttributes = array(
        'class' => 'statesummary',
    );

    public function __construct($states)
    {
        $inUse = array();
        foreach ($states as $state => $count) {
            $stateName = HostState::hostSeverityStateName($state);
            if (! array_key_exists($stateName, $inUse)) {
                $inUse[$stateName] = array();
            }

            if (($state & HostState::FLAG_NONE) === HostState::FLAG_NONE) {
                $inUse[$stateName]['unhandled'] = $count;
            } else {
                $inUse[$stateName]['handled'] = $count;
            }
        }

        foreach ($inUse as $stateName => $counts) {
            $ul = Element::create('ul', array('class' => $stateName));
            if (array_key_exists('unhandled', $counts)) {
                $ul->add(
                    Element::create('li')->setContent(
                        Link::create(
                            $counts['unhandled'],
                            '#'
                        )
                    )
                );
            }
            if (array_key_exists('handled', $counts)) {
                $ul->add(
                    Element::create('li', array('class' => 'handled'))
                        ->setContent(
                            Link::create(
                                $counts['handled'],
                                '#'
                            )
                        )
                );
            }

            $this->add($ul);
        }
    }
}
