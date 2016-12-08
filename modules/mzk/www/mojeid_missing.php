<?php
$config = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($config, 'mzk:mojeid_missing.tpl.php');
$t->data['pageid'] = 'mojeid_missing';
$t->data['header'] = 'MojeID missing';
$t->data['backlink'] = SimpleSAML\Module::getModuleURL('mzk/mojeid_missing.php');
$t->data['m'] = $m;
$t->show();
