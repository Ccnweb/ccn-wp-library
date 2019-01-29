<?php 

define( 'CCN_LIBRARY_PLUGIN_DIR', '..' );

require_once(CCN_LIBRARY_PLUGIN_DIR . '/log.php');
use \ccn\lib\log as log;

/* echo log\error('titre', array('Louez ', 'le ', 'Seigneur !'));

echo log\write('INFO', 'Louez le Seigneur', 'En tous temps');

echo log\error('titre', 'Lodate Dio voi tutti servi suoi !!!');
echo log\error('titre', array('Louez ', 'le ', 'Seigneur !')); */

$res = log\clean_old_logs();
echo count($res)." files found : ".json_encode($res)."\n\n";

?>