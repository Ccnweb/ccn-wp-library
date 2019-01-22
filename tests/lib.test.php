<?php

define( 'CCN_LIBRARY_PLUGIN_DIR', '..' );

require_once(CCN_LIBRARY_PLUGIN_DIR . '/lib.php');
use \ccn\lib as lib;

// ============================================
//  TEST parseTemplateString
// ============================================

$sujet = 'Louez {{dieu}} vous {{qui}}';
$data = array(
    'dieu' => 'le Seigneur Dieu de l\'univers',
    'qui' => 'tous rassemblés',
);
$res = lib\parseTemplateString($sujet, $data);
echo $res."\n";

?>