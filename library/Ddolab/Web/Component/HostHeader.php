<?php

namespace Icinga\Module\Ddolab\Web\Component;

use Icinga\Date\DateFormatter;
use Icinga\Module\Businessprocess\Html\BaseElement;
use Icinga\Module\Businessprocess\Html\Container;
use Icinga\Module\Businessprocess\Html\Element;
use Icinga\Module\Businessprocess\Html\HtmlTag;
use Icinga\Module\Ddolab\HostObject;
use Icinga\Module\Ddolab\HostState;

class HostHeader extends BaseElement
{
    protected $contentSeparator = "\n";

    /** @var string */
    protected $tag = 'div';

    /** @inheritdoc */
    protected $defaultAttributes = array(
        'class' => 'object-header',
    );

    public function __construct(HostObject $object, HostState $state)
    {
        $this->add(
            $this->createStateElement($state)
        )->addContent(
            Container::create(
                array('class' => 'header-details'),
                $this->renderHostHeaderDetails($object)
            )
        );
    }

    protected function renderHostHeaderDetails(HostObject $host)
    {
        return array(
            HtmlTag::h1($host->get('name')),
            Element::create('span', array('class' => 'ipaddress'))
                ->addContent($host->get('address'))
        );
    }

    protected function createStateElement(HostState $state)
    {
        return Element::create(
            'span',
            array('class' => array_merge(array('state'), $this->getStateClasses($state)))
        )->addContent(
            strtoupper($state->getStateName())
        )->add(
            Element::create(
                'span',
                array('class' => array('relative-time', 'time-since'))
            )->setContent(
                DateFormatter::timeSince(
                    $state->get('last_state_change') / 1000000
                )
            )
        );
    }

    protected function getStateClasses(HostState $state)
    {
        $classes = array($state->getStateName());

        if ($state->isProblem()) {
            $classes[] = 'problem';
        }

        if ($state->isAcknowledged()) {
            $classes[] = 'handled';
            $classes[] = 'acknowledged';
        }

        if ($state->isInDowntime()) {
            $classes[] = 'handled';
            $classes[] = 'in_downtime';
        }

        return $classes;
    }
}
