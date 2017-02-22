<?php
if (array_key_exists('StateId', $_REQUEST)) {
   $as = new SimpleSAML_Auth_Simple('mzk');
   $as->logout(SimpleSAML\Module::getModuleURL('mzk/outside_library.php'));
}
$config = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($config, 'mzk:outside_library.tpl.php');
$t->data['pageid'] = 'outside_library';
$t->data['header'] = 'Oustide Library';
$t->data['backlink'] = SimpleSAML\Module::getModuleURL('mzk/outside_library.php');
//$t->data['m'] = $m;
$t->show();
