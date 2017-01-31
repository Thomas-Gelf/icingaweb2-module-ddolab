<?php

$section = $this->menuSection(N_('DDO Lab'))
    ->setIcon('gauge')
    ->setPriority(45);

$section->add(N_('Control center'))
    ->setUrl('ddolab/control')
    ->setPriority(30);
$section->add(N_('Hosts'))
    ->setUrl('ddolab/hosts')
    ->setPermission('ddolab/hosts')
    ->setPriority(31);
