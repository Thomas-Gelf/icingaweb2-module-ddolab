<?php

namespace Icinga\Module\Ddolab\Web;

use Icinga\Module\Businessprocess\Html\BaseElement;
use Icinga\Module\Businessprocess\Html\Element;
use Icinga\Module\Businessprocess\Html\Html;
use Icinga\Module\Businessprocess\Html\HtmlTag;
use Icinga\Module\Ddolab\View\ListView;

class SimpleTable extends BaseElement
{
    protected $contentSeparator = ' ';

    /** @var string */
    protected $tag = 'table';

    protected $defaultAttributes = array('class' => 'simple-table');

    private $header;

    private $body;

    private $footer;

    protected $view;

    public function __construct(ListView $view)
    {
        $this->view = $view;
    }

    public function getColumnsToBeRendered()
    {
        return array('host', 'state');
    }

    public function generateHeader()
    {
        $thead = Element::create('thead');
        $tr = Element::create('tr');
        $thead->add($tr);
        foreach ($this->getColumnsToBeRendered() as $column) {
            $thead->add(
                Element::create('th')->setContent($column)
            );
        }
        return $thead;
    }

    protected function renderRow($row)
    {
        $tr = Element::create('tr');
        foreach ($this->getColumnsToBeRendered() as $column) {
            $td = Element::create('td');
            if (property_exists($row, $column)) {
                $td->setContent($row->$column);
            }
            $tr->add($td);
        }

        return $tr;
    }

    public function renderRows()
    {
        $tbody = Element::create('tbody')->setSeparator("\n");
        foreach ($this->view->fetchRows() as $row) {
            $tbody->add($this->renderRow($row));
        }

        return $tbody;
    }

    public function header()
    {
        if ($this->header === null) {
            $this->header = $this->generateHeader();
            $this->add($this->header);
        }

        return $this->header;
    }

    public function body()
    {
        if ($this->body === null) {
            $this->body = $this->renderRows();
            $this->add($this->body);
        }

        return $this->body;
    }

    public function renderContent()
    {
        $this->header();
        $this->body();
        return parent::renderContent();
    }
}
